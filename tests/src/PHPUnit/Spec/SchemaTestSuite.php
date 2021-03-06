<?php

namespace Swaggest\JsonSchema\Tests\PHPUnit\Spec;

use Swaggest\JsonSchema\Constraint\Format;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\RemoteRef\Preloaded;

abstract class SchemaTestSuite extends \PHPUnit_Framework_TestCase
{
    const SCHEMA_VERSION = Schema::VERSION_DRAFT_04;

    public static function getProvider()
    {
        static $refProvider = null;

        if (null === $refProvider) {
            $refProvider = new Preloaded();
            $refProvider
                ->setSchemaData(
                    'http://localhost:1234/integer.json',
                    json_decode(file_get_contents(__DIR__
                        . '/../../../../spec/JSON-Schema-Test-Suite/remotes/integer.json')))
                ->setSchemaData(
                    'http://localhost:1234/subSchemas.json',
                    json_decode(file_get_contents(__DIR__
                        . '/../../../../spec/JSON-Schema-Test-Suite/remotes/subSchemas.json')))
                ->setSchemaData(
                    'http://localhost:1234/name.json',
                    json_decode(file_get_contents(__DIR__
                        . '/../../../../spec/JSON-Schema-Test-Suite/remotes/name.json')))
                ->setSchemaData(
                    'http://localhost:1234/folder/folderInteger.json',
                    json_decode(file_get_contents(__DIR__
                        . '/../../../../spec/JSON-Schema-Test-Suite/remotes/folder/folderInteger.json')));
        }

        return $refProvider;
    }

    abstract protected function skipTest($name);

    abstract protected function specProvider();

    abstract protected function specOptionalProvider();

    protected function provider($path)
    {
        if (!file_exists($path)) {
            //$this->markTestSkipped('No spec tests found, please run `git submodule bla-bla`');
        }

        $testCases = array();

        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    if ('.json' !== substr($entry, -5)) {
                        continue;
                    }

                    //if ($entry !== 'refRemote.json') {
                    //continue;
                    //}

                    //echo "$entry\n";
                    /** @var _SpecTest[] $tests */
//                    $tests = json_decode(file_get_contents($path . '/' . $entry), false, 512, JSON_BIGINT_AS_STRING);
                    $tests = json_decode(file_get_contents($path . '/' . $entry));

                    foreach ($tests as $test) {
                        foreach ($test->tests as $c => $case) {
                            /*if ($case->description !== 'changed scope ref invalid') {
                                continue;
                            }
                            */

                            $name = $entry . ' ' . $test->description . ': ' . $case->description . ' [' . $c . ']';
                            if (!isset($test->schema)) {
                                if (isset($test->schemas)) {
                                    foreach ($test->schemas as $i => $schema) {
                                        $testCases[$name . '_' . $i] = array(
                                            'schema' => $schema,
                                            'data' => $case->data,
                                            'isValid' => $case->valid,
                                            'name' => $name,
                                        );
                                    }
                                }
                                continue;
                            }
                            $testCases[$name] = array(
                                'schema' => $test->schema,
                                'data' => $case->data,
                                'isValid' => $case->valid,
                                'name' => $name,
                            );
                        }
                    }
                }
            }
            closedir($handle);
        }

        //print_r($testCases);

        return $testCases;
    }

    /**
     * @dataProvider specProvider
     * @param $schemaData
     * @param $data
     * @param $isValid
     * @param $name
     * @throws \Exception
     */
    public function testSpec($schemaData, $data, $isValid, $name)
    {
        if ($this->skipTest($name)) {
            $this->markTestSkipped();
            return;
        }
        $this->runSpecTest($schemaData, $data, $isValid, $name, static::SCHEMA_VERSION);
    }

    /**
     * @dataProvider specOptionalProvider
     * @param $schemaData
     * @param $data
     * @param $isValid
     * @param $name
     * @throws \Exception
     */
    public function testSpecOptional($schemaData, $data, $isValid, $name)
    {
        if ($this->skipTest($name)) {
            $this->markTestSkipped();
            return;
        }
        $this->runSpecTest($schemaData, $data, $isValid, $name, static::SCHEMA_VERSION);
    }

    protected function makeOptions($version)
    {
        $refProvider = static::getProvider();

        $options = new Context();
        $options->setRemoteRefProvider($refProvider);
        $options->version = $version;
        $options->strictBase64Validation = true;

        return $options;
    }

    /**
     * @param $schemaData
     * @param $data
     * @param $isValid
     * @param $name
     * @param $version
     * @throws \Exception
     */
    protected function runSpecTest($schemaData, $data, $isValid, $name, $version)
    {
        Format::$strictDateTimeValidation = true;
        $actualValid = true;
        $error = '';
        try {
            $options = $this->makeOptions($version);
            $schema = Schema::import($schemaData, $options);

            // import with defaults applied
            $schema->in($data, $options);

            // default is not required to pass validation, so result might be invalid
            // for back-exporting defaults have to be disabled
            $options->applyDefaults = false;
            $imported = $schema->in($data, $options);

            $exported = $schema->out($imported);

            $imported = $schema->in($exported, $options);
            $exported2 = $schema->out($imported);

            $this->assertEquals($exported2, $exported, $name);
        } catch (InvalidValue $exception) {
            $actualValid = false;
            $error = $exception->getMessage();
        }

        $this->assertSame($isValid, $actualValid,
            "Test: $name\n"
            . "Schema:\n" . json_encode($schemaData, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES)
            . "\nData:\n" . json_encode($data, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES)
            . "\nError: " . $error . "\n");

    }
}

/**
 * @property $description
 * @property $schema
 * @property $schemas
 * @property _SpecTestCase[] $tests
 */
class _SpecTest
{
}

/**
 * @property $description
 * @property $data
 * @property bool $valid
 */
class _SpecTestCase
{
}