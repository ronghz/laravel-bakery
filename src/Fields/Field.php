<?php

namespace Bakery\Fields;

use Illuminate\Support\Arr;
use Bakery\Support\Arguments;
use Bakery\Support\TypeRegistry;
use Illuminate\Support\Facades\Gate;
use Bakery\Types\Definitions\RootType;
use function Bakery\is_callable_tuple;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Auth\Access\AuthorizationException;

class Field
{
    /**
     * @var \Bakery\Support\TypeRegistry
     */
    protected $registry;

    /**
     * @var \GraphQL\Type\Definition\Type
     */
    protected $type;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $accessor;

    /**
     * @var bool
     */
    protected $list = false;

    /**
     * @var bool
     */
    protected $nullable = false;

    /**
     * @var bool
     */
    protected $nullableItems = false;

    /**
     * @var bool
     */
    protected $fillable = true;

    /**
     * @var array
     */
    protected $with;

    /**
     * @var bool
     */
    protected $searchable = false;

    /**
     * @var bool
     */
    protected $unique = false;

    /**
     * @var mixed
     */
    protected $storePolicy;

    /**
     * @var mixed
     */
    protected $viewPolicy;

    /**
     * @var callable
     */
    protected $resolver;

    /**
     * Construct a new field.
     *
     * @param \Bakery\Support\TypeRegistry $registry
     * @param \Bakery\Types\Definitions\RootType|null $type
     */
    public function __construct(TypeRegistry $registry, RootType $type = null)
    {
        $this->registry = $registry;

        if ($type) {
            $this->type = $type;
        }
    }

    /**
     * @return \Bakery\Support\TypeRegistry
     */
    public function getRegistry(): TypeRegistry
    {
        return $this->registry;
    }

    /**
     * @param \Bakery\Support\TypeRegistry $registry
     * @return $this
     */
    public function setRegistry(TypeRegistry $registry): self
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * Define the type of the field.
     * This method can be overridden.
     *
     * @return \Bakery\Types\Definitions\RootType
     */
    protected function type(): RootType
    {
        return $this->type;
    }

    /**
     * Return the type of the field.
     *
     * @return \Bakery\Types\Definitions\RootType
     */
    public function getType(): RootType
    {
        $type = $this->type();

        $type->nullable($this->isNullable());
        $type->list($this->isList());
        $type->nullableItems($this->hasNullableItems());

        return $type->setRegistry($this->getRegistry());
    }

    /**
     * Return if the field represents a relationship.
     */
    public function isRelationship(): bool
    {
        return $this instanceof EloquentField || $this instanceof PolymorphicField;
    }

    /**
     * Define the name of the field.
     *
     * This method can be overridden when extending the Field.
     *
     * @return string
     */
    protected function name(): string
    {
        return $this->name;
    }

    /**
     * Get the name of the type.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name();
    }

    /**
     * Set the name of the type.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the description of the field.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the description of the field.
     *
     * @param string $description
     * @return $this
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Return the name of the database column associated with this field.
     *
     * @return string|null
     */
    public function getAccessor(): ?string
    {
        return $this->accessor;
    }

    /**
     * Define the name of the database column associated with this field.
     *
     * @param string $accessor
     * @return Field
     */
    public function accessor(string $accessor): self
    {
        $this->accessor = $accessor;

        return $this;
    }

    /**
     * Set the args of the field.
     *
     * @param array $args
     * @return $this
     */
    public function args(array $args): self
    {
        $this->args = $args;

        return $this;
    }

    /**
     * Get the args of the field.
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args ?? [];
    }

    /**
     * @param bool $list
     * @return $this
     */
    public function list(bool $list = true): self
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @return bool
     */
    public function isList(): bool
    {
        return $this->list;
    }

    /**
     * Set if the field is nullable.
     *
     * @param bool $nullable
     * @return $this
     */
    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * Return if the field is nullable.
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Set if the field has nullable items.
     *
     * @param bool $nullable
     * @return \Bakery\Fields\Field
     */
    public function nullableItems(bool $nullable = true): self
    {
        $this->nullableItems = $nullable;

        return $this;
    }

    /**
     * Return if the field has nullable items.
     *
     * @return bool
     */
    public function hasNullableItems(): bool
    {
        return $this->nullableItems;
    }

    /**
     * Set if the field is fillable.
     *
     * @param bool $fillable
     * @return $this
     */
    public function fillable(bool $fillable = true): self
    {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * Set the field to read only.
     *
     * @return $this
     */
    public function readOnly(): self
    {
        $this->fillable(false);

        return $this;
    }

    /**
     * Return if the field is fillable.
     *
     * @return bool
     */
    public function isFillable(): bool
    {
        return $this->fillable;
    }

    /**
     * Set the relations that should be eager loaded.
     *
     * @param  string[]|string  $relations
     * @return Field
     */
    public function with($relations): self
    {
        $this->with = Arr::wrap($relations);

        return $this;
    }

    /**
     * Get the relations that should be eager loaded.
     */
    public function getWith(): ?array
    {
        return $this->with;
    }

    /**
     * Set if the field is searchable.
     *
     * @param bool $searchable
     * @return \Bakery\Fields\Field
     */
    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Return if the field is searchable.
     *
     * @return bool
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Set if the field is unique.
     *
     * @param bool $unique
     * @return \Bakery\Fields\Field
     */
    public function unique(bool $unique = true): self
    {
        $this->unique = $unique;

        return $this;
    }

    /**
     * Return if the field is unique.
     *
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * Set the story policy.
     *
     * @param $policy
     * @return \Bakery\Fields\Field
     */
    public function storePolicy($policy): self
    {
        $this->storePolicy = $policy;

        return $this;
    }

    /**
     * Set the store policy with a callable.
     *
     * @param callable $closure
     * @return $this
     */
    public function canStore(callable $closure): self
    {
        return $this->storePolicy($closure);
    }

    /**
     * Set the store policy with a reference to a policy method.
     *
     * @param string $policy
     * @return $this
     */
    public function canStoreWhen(string $policy): self
    {
        return $this->storePolicy($policy);
    }

    /**
     * Set the view policy.
     *
     * @param $policy
     * @return $this
     */
    public function viewPolicy($policy): self
    {
        $this->viewPolicy = $policy;

        return $this;
    }

    /**
     * Set the store policy with a callable.
     *
     * @param callable $closure
     * @return $this
     */
    public function canSee(callable $closure = null): self
    {
        return $this->viewPolicy($closure);
    }

    /**
     * Set the store policy with a reference to a policy method.
     *
     * @param string $policy
     * @return $this
     */
    public function canSeeWhen(string $policy): self
    {
        return $this->viewPolicy($policy);
    }

    /**
     * @return mixed
     */
    public function getViewPolicy()
    {
        return $this->viewPolicy;
    }

    /**
     * Set the resolver.
     *
     * @param $resolver
     * @return $this
     */
    public function resolve(callable $resolver): self
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * Resolve the field.
     *
     * @param $root
     * @param array $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return mixed|null
     * @throws AuthorizationException
     */
    public function resolveField($root, array $args, $context, ResolveInfo $info)
    {
        $accessor = $this->getAccessor() ?: $info->fieldName;
        $args = new Arguments($args);

        if (isset($this->viewPolicy)) {
            if (! $this->authorizeToRead($root, $info->fieldName)) {
                return null;
            }
        }

        if (isset($this->resolver)) {
            return call_user_func_array($this->resolver, [$root, $accessor, $args, $context, $info]);
        }

        return self::defaultResolver($root, $accessor, $args, $context, $info);
    }

    /**
     * Determine if the current user can read the field of the model or throw an exception if not nullable.
     *
     * @param mixed $source
     * @param string $fieldName
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToRead($source, $fieldName)
    {
        $result = $this->authorizedToRead($source);
        if ($result || $this->nullable) {
            return $result;
        }

        throw new AuthorizationException('Cannot read property "'.$fieldName.'" of '.get_class($source));
    }

    /**
     * Determine if the current user can read the field of the model.
     *
     * @param mixed $source
     * @return bool
     */
    public function authorizedToRead($source): bool
    {
        $policy = $this->viewPolicy;

        // Check if there is a policy.
        if (! $policy) {
            return true;
        }

        // Check if the policy method is callable
        if (($policy instanceof \Closure || is_callable_tuple($policy)) && $policy($source)) {
            return true;
        }

        // Check if there is a policy with this name
        if (is_string($policy) && Gate::check($policy, $source)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the current user can store the value on the model or throw an exception.
     *
     * @param mixed $source
     * @param mixed $value
     * @param string $fieldName
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToStore($source, $value, $fieldName): bool
    {
        if ($this->authorizedToRead($source)) {
            $result = $this->authorizedToStore($source, $value);

            if ($result) {
                return $result;
            }
        }

        throw new AuthorizationException('Cannot set property "'.$fieldName.'" of '.get_class($source));
    }

    /**
     * Determine if the current user can store the value on the model.
     *
     * @param mixed $source
     * @param mixed $value
     * @return bool
     */
    public function authorizedToStore($source, $value): bool
    {
        $policy = $this->storePolicy;

        // Check if there is a policy.
        if (! $policy) {
            return true;
        }

        // Check if the policy method is a closure.
        if (($policy instanceof \Closure || is_callable_tuple($policy)) && $policy($source, $value)) {
            return true;
        }

        // Check if there is a policy with this name
        if (is_string($policy) && Gate::check($policy, [$source, $value])) {
            return true;
        }

        return false;
    }

    /**
     * Convert the field to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'type' => $this->getType()->toType(),
            'args' => collect($this->getArgs())->map(function (RootType $type) {
                return $type->toType();
            })->toArray(),
            'resolve' => [$this, 'resolveField'],
            'description' => $this->getDescription(),
        ];
    }

    /**
     * The default resolver for resolving the value of the type.
     * This gets called when there is no custom resolver defined.
     *
     * @param string $accessor
     * @param Arguments $args
     * @param $root
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return mixed|null
     */
    public static function defaultResolver($root, string $accessor, Arguments $args, $context, ResolveInfo $info)
    {
        $property = null;

        if (Arr::accessible($root)) {
            $property = $root[$accessor];
        } elseif (is_object($root)) {
            $property = $root->{$accessor};
        }

        return $property instanceof \Closure ? $property($args, $root, $context, $info) : $property;
    }

    /**
     * Invoked when the object is being serialized.
     * Returns the field that should be serialized.
     *
     * @return array
     */
    public function __sleep()
    {
        return ['registry'];
    }
}
