<?php

namespace Ludelix\Validation\Rules;

use Ludelix\Interface\Validation\RuleInterface;
use Ludelix\Exceptions\Validation\RuleNotFoundException;
use Ludelix\Exceptions\Validation\InvalidRuleException;

/**
 * RuleFactory - Factory for creating validation rules
 * 
 * Creates rule instances based on rule string
 */
class RuleFactory
{
    protected array $ruleMap = [
        // Basic rules
        'required' => \Ludelix\Validation\Rules\Basic\RequiredRule::class,
        'string' => \Ludelix\Validation\Rules\Basic\StringRule::class,
        'numeric' => \Ludelix\Validation\Rules\Basic\NumericRule::class,
        'boolean' => \Ludelix\Validation\Rules\Basic\BooleanRule::class,
        'array' => \Ludelix\Validation\Rules\Basic\ArrayRule::class,
        
        // String rules
        'email' => \Ludelix\Validation\Rules\String\EmailRule::class,
        'url' => \Ludelix\Validation\Rules\String\UrlRule::class,
        'ip' => \Ludelix\Validation\Rules\String\IpRule::class,
        'regex' => \Ludelix\Validation\Rules\String\RegexRule::class,
        'not_regex' => \Ludelix\Validation\Rules\String\NotRegexRule::class,
        'alpha' => \Ludelix\Validation\Rules\String\AlphaRule::class,
        'alpha_num' => \Ludelix\Validation\Rules\String\AlphaNumRule::class,
        'alpha_dash' => \Ludelix\Validation\Rules\String\AlphaDashRule::class,
        'lowercase' => \Ludelix\Validation\Rules\String\LowercaseRule::class,
        'uppercase' => \Ludelix\Validation\Rules\String\UppercaseRule::class,
        'slug' => \Ludelix\Validation\Rules\String\SlugRule::class,
        'uuid' => \Ludelix\Validation\Rules\String\UuidRule::class,
        'ulid' => \Ludelix\Validation\Rules\String\UlidRule::class,
        'starts_with' => \Ludelix\Validation\Rules\String\StartsWithRule::class,
        'ends_with' => \Ludelix\Validation\Rules\String\EndsWithRule::class,
        'contains' => \Ludelix\Validation\Rules\String\ContainsRule::class,
        'not_contains' => \Ludelix\Validation\Rules\String\NotContainsRule::class,
        
        // Numeric rules
        'integer' => \Ludelix\Validation\Rules\Numeric\IntegerRule::class,
        'float' => \Ludelix\Validation\Rules\Numeric\FloatRule::class,
        'decimal' => \Ludelix\Validation\Rules\Numeric\DecimalRule::class,
        'between' => \Ludelix\Validation\Rules\Numeric\BetweenRule::class,
        'min' => \Ludelix\Validation\Rules\Numeric\MinRule::class,
        'max' => \Ludelix\Validation\Rules\Numeric\MaxRule::class,
        'size' => \Ludelix\Validation\Rules\Numeric\SizeRule::class,
        'digits' => \Ludelix\Validation\Rules\Numeric\DigitsRule::class,
        'digits_between' => \Ludelix\Validation\Rules\Numeric\DigitsBetweenRule::class,
        'multiple_of' => \Ludelix\Validation\Rules\Numeric\MultipleOfRule::class,
        'divisible_by' => \Ludelix\Validation\Rules\Numeric\DivisibleByRule::class,
        
        // Comparison rules
        'same' => \Ludelix\Validation\Rules\Comparison\SameRule::class,
        'different' => \Ludelix\Validation\Rules\Comparison\DifferentRule::class,
        'gt' => \Ludelix\Validation\Rules\Comparison\GtRule::class,
        'gte' => \Ludelix\Validation\Rules\Comparison\GteRule::class,
        'lt' => \Ludelix\Validation\Rules\Comparison\LtRule::class,
        'lte' => \Ludelix\Validation\Rules\Comparison\LteRule::class,
        
        // Date rules
        'date' => \Ludelix\Validation\Rules\Date\DateRule::class,
        'date_equals' => \Ludelix\Validation\Rules\Date\DateEqualsRule::class,
        'date_format' => \Ludelix\Validation\Rules\Date\DateFormatRule::class,
        'before' => \Ludelix\Validation\Rules\Date\BeforeRule::class,
        'before_or_equal' => \Ludelix\Validation\Rules\Date\BeforeOrEqualRule::class,
        'after' => \Ludelix\Validation\Rules\Date\AfterRule::class,
        'after_or_equal' => \Ludelix\Validation\Rules\Date\AfterOrEqualRule::class,
        'timezone' => \Ludelix\Validation\Rules\Date\TimezoneRule::class,
        
        // File rules
        'file' => \Ludelix\Validation\Rules\File\FileRule::class,
        'image' => \Ludelix\Validation\Rules\File\ImageRule::class,
        'video' => \Ludelix\Validation\Rules\File\VideoRule::class,
        'audio' => \Ludelix\Validation\Rules\File\AudioRule::class,
        'mimes' => \Ludelix\Validation\Rules\File\MimesRule::class,
        'mimetypes' => \Ludelix\Validation\Rules\File\MimeTypesRule::class,
        'dimensions' => \Ludelix\Validation\Rules\File\DimensionsRule::class,
        'max_width' => \Ludelix\Validation\Rules\File\MaxWidthRule::class,
        'max_height' => \Ludelix\Validation\Rules\File\MaxHeightRule::class,
        'min_width' => \Ludelix\Validation\Rules\File\MinWidthRule::class,
        'min_height' => \Ludelix\Validation\Rules\File\MinHeightRule::class,
        
        // Database rules
        'unique' => \Ludelix\Validation\Rules\Database\UniqueRule::class,
        'exists' => \Ludelix\Validation\Rules\Database\ExistsRule::class,
        'distinct' => \Ludelix\Validation\Rules\Database\DistinctRule::class,
        
        // Network rules
        'active_url' => \Ludelix\Validation\Rules\Network\ActiveUrlRule::class,
        'dns' => \Ludelix\Validation\Rules\Network\DnsRule::class,
        'ipv4' => \Ludelix\Validation\Rules\Network\Ipv4Rule::class,
        'ipv6' => \Ludelix\Validation\Rules\Network\Ipv6Rule::class,
        
        // Custom rules
        'callback' => \Ludelix\Validation\Rules\Custom\CallbackRule::class,
        'closure' => \Ludelix\Validation\Rules\Custom\ClosureRule::class,
        'custom' => \Ludelix\Validation\Rules\Custom\CustomRule::class,
    ];

    /**
     * Create a rule instance
     */
    public function create(string $rule): RuleInterface
    {
        $ruleName = $this->parseRuleName($rule);
        $parameters = $this->parseRuleParameters($rule);

        if (!isset($this->ruleMap[$ruleName])) {
            throw new RuleNotFoundException($ruleName);
        }

        $ruleClass = $this->ruleMap[$ruleName];
        
        if (!class_exists($ruleClass)) {
            throw new RuleNotFoundException($ruleName, "Rule class '{$ruleClass}' not found");
        }

        $ruleInstance = new $ruleClass();
        
        if (!$ruleInstance instanceof RuleInterface) {
            throw new InvalidRuleException($ruleName, $parameters, "Rule class must implement RuleInterface");
        }

        return $ruleInstance;
    }

    /**
     * Parse rule name from rule string
     */
    protected function parseRuleName(string $rule): string
    {
        $parts = explode(':', $rule);
        return $parts[0];
    }

    /**
     * Parse rule parameters from rule string
     */
    protected function parseRuleParameters(string $rule): array
    {
        $parts = explode(':', $rule);
        
        if (count($parts) < 2) {
            return [];
        }

        $parameters = $parts[1];
        
        if (str_contains($parameters, ',')) {
            return array_map('trim', explode(',', $parameters));
        }

        return [$parameters];
    }

    /**
     * Register a custom rule
     */
    public function register(string $name, string $class): void
    {
        $this->ruleMap[$name] = $class;
    }

    /**
     * Check if rule exists
     */
    public function hasRule(string $name): bool
    {
        return isset($this->ruleMap[$name]);
    }

    /**
     * Get all registered rules
     */
    public function getRegisteredRules(): array
    {
        return array_keys($this->ruleMap);
    }
} 