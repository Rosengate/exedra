<?php

namespace Exedra\Routeller;

use Exedra\Routeller\Contracts\PropertyParser;
use Exedra\Routeller\Contracts\RoutePropertiesReader;
use Exedra\Support\DotArray;

/**
 * Almost entire codes here is fully credited to :
 * https://github.com/marcioAlmada/annotations
 * I decided to decouple from their package due to the conflict with
 * nikic/php-parser requirement and to ease future maintenance
 *
 * Class AnnotationsReader
 * @package Exedra\Routeller
 */
class AnnotationsReader implements RoutePropertiesReader
{
    const TOKEN_ANNOTATION_IDENTIFIER = '@';

    protected static $exceptions = array(
        'return' => 1,
        'param' => 1,
        'throws' => 1,
        'package' => 1
    );

    const TOKEN_ANNOTATION_NAME = '[a-zA-Z\_\-\\\][a-zA-Z0-9\_\-\.\\\]*';

    /**
     * The regex to extract data from a single line
     *
     * @var string
     */
    protected $dataPattern;

    /**
     * @var PropertyParser[]
     */
    private $propertyParsers;

    /**
     * Parser constructor
     * @param PropertyParser[] $propertyParsers
     */
    public function __construct(array $propertyParsers = [])
    {
        $this->dataPattern = '/(?<=\\'. self::TOKEN_ANNOTATION_IDENTIFIER .')('
            . self::TOKEN_ANNOTATION_NAME
            .')(((?!\s\\'. self::TOKEN_ANNOTATION_IDENTIFIER .').)*)/s';

        $this->propertyParsers = $propertyParsers;
    }

    public function addExceptionTags(array $tags)
    {
        static::$exceptions = array_merge(static::$exceptions, array_flip($tags));
    }

    /**
     * @param \Reflector|\ReflectionMethod $reflection
     * @return array
     */
    public function readProperties(\Reflector $reflection)
    {
        $doc = $reflection->getDocComment();
        /*if ($this->cache) {
            $key = $this->cache->getKey($doc);
            $ast = $this->cache->get($key);
            if (!$ast) {
                $ast = $this->parser->parse($doc);
                $this->cache->set($key, $ast);
            }
        } else {
            $ast = $this->parser->parse($doc);
        }*/
        $ast = $this->parse($doc);

        $properties = array();

        foreach ($ast as $key => $value) {
            if (isset($this->propertyParsers[$key])) {
                $parser = $this->propertyParsers[$key];

                $key = $parser->keyTo($reflection, $key);
                $value = $parser->valueTo($reflection, $value);
            }

            if (isset(static::$exceptions[$key]))
                continue;

            if (strpos($key, 'attr.') === 0 && is_string($value) && strpos($value, '[] ') === 0) {
                $key .= '[]';
                $value = substr_replace($value, '', 0, strlen('[] '));
            }

            DotArray::set($properties, $key, $value);
        }

        return $properties;
    }

    /**
     * Parse a given docblock
     *
     * @param  string $docblock
     * @return array
     */
    public function parse($docblock)
    {
        $docblock = $this->getDocblockTagsSection($docblock);
        $annotations = $this->parseAnnotations($docblock);
        foreach ($annotations as &$value) {
            if (1 == count($value)) {
                $value = $value[0];
            }
        }

        return $annotations;
    }

    /**
     * Filters docblock tags section, removing unwanted long and short descriptions
     *
     * @param  string $docblock A docblok string without delimiters
     * @return string Tag section from given docblock
     */
    protected function getDocblockTagsSection($docblock)
    {
        $docblock = $this->sanitizeDocblock($docblock);
        preg_match('/^\s*\\'.self::TOKEN_ANNOTATION_IDENTIFIER.'/m', $docblock, $matches, PREG_OFFSET_CAPTURE);

        // return found docblock tag section or empty string
        return isset($matches[0]) ? substr($docblock, $matches[0][1]) : '';
    }

    /**
     * Filters docblock delimiters
     *
     * @param  string $docblock A raw docblok string
     * @return string A docblok string without delimiters
     */
    protected function sanitizeDocblock($docblock)
    {
        return preg_replace('/\s*\*\/$|^\s*\*\s{0,1}|^\/\*{1,2}/m', '', $docblock);
    }

    /**
     * Creates raw [annotation => value, [...]] tree
     *
     * @param  string $str
     * @return array
     */
    protected function parseAnnotations($str)
    {
        $annotations = [];
        preg_match_all($this->dataPattern, $str, $found);
        foreach ($found[2] as $key => $value) {
            $annotations[ $this->sanitizeKey($found[1][$key]) ][] = $this->parseValue(trim($value), $found[1][$key]);
        }

        return $annotations;
    }

    /**
     * Parse a single annotation value
     *
     * @param  string $value
     * @param  string $key
     * @return mixed
     */
    protected function parseValue($value, $key = null)
    {
        if ('' === $value) return true; // implicit boolean

        $json = json_decode($value, true);

        if (JSON_ERROR_NONE === json_last_error()) {
            return $json;
        } elseif (false !== ($int = filter_var($value, FILTER_VALIDATE_INT))) {
            return $int;
        } elseif (false !== ($float = filter_var($value, FILTER_VALIDATE_FLOAT))) {
            return $float;
        }

        return $value;
    }

    /**
     * Just a hook so derived parsers can transform annotation identifiers before they go to AST
     *
     * @param  string $key
     * @return string
     */
    protected function sanitizeKey($key)
    {
        return $key;
    }
}