<?php

/**
 * This file is part of the Geotools library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Geotools\Tests\Batch;

use Geotools\Tests\TestCase;
use Geotools\Batch\Batch;
use Geocoder\Provider\ProviderInterface;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class BatchTest extends TestCase
{
    protected $geocoder;
    protected $providers;
    protected $data;

    protected function setUp()
    {
        $this->data = array(
            'latitude' => 48.8234055,
            'longitude' => '2.3072664',
        );

        $this->providers = array(
            new MockProvider('provider1'),
            new MockProvider('provider2'),
            new MockProvider('provider3'),
        );

        $this->geocoder = $this->getMockGeocoderReturns($this->providers);
    }

    public function testConstructorShouldAcceptGeocoderInterface()
    {
        new TestableBatch($this->getStubGeocoder());
    }

    public function testConstructorShouldSetGeocoderInterface()
    {
        $batch = new TestableBatch($this->getStubGeocoder());
        $geocoder = $batch->getGeocoder();

        $this->assertTrue(is_object($geocoder));
        $this->assertInstanceOf('Geocoder\GeocoderInterface', $geocoder);
    }

    public function testGeocodeShouldReturnBatchInterface()
    {
        $batch = new TestableBatch($this->geocoder);
        $batchReturned = $batch->geocode('foo');

        $this->assertTrue(is_object($batchReturned));
        $this->assertInstanceOf('Geotools\Batch\Batch', $batchReturned);
        $this->assertInstanceOf('Geotools\Batch\BatchInterface', $batchReturned);
        $this->assertSame($batch, $batchReturned);
    }

    public function testGeocodeShouldMadeCorrectTasksArrayToCompute()
    {
        $batch = new TestableBatch($this->geocoder);
        $batch->geocode('foo');
        $tasks = $batch->getTasks();

        $this->assertTrue(is_array($tasks));
        $this->assertCount(3, $tasks);
    }

    public function testGeocodeShouldMadeCorrectTasksArrayToComputeWithManyValues()
    {
        $batch = new TestableBatch($this->geocoder);
        $batch->geocode(array('foo', 'bar', 'baz'));
        $tasks = $batch->getTasks();

        $this->assertTrue(is_array($tasks));
        $this->assertCount(9, $tasks);
    }

    /**
     * @expectedException Geotools\Exception\InvalidArgumentException
     * @expectedExceptionMessage The argument should be a string or an array of strings to geocode.
     * @dataProvider invalidValuesProvider
     */
    public function testGeocodeShouldThrowInvalidArgumentException($values)
    {
        $batch = new TestableBatch($this->geocoder);
        $batch->geocode($values);
    }

    public function invalidValuesProvider()
    {
        return array(
            array(0),
            array(0.0),
            array(1234, 5678.0),
            array(array()),
            array(array(), array()),
            array(' '),
            array(' ', ' '),
        );
    }

    public function testReverseShouldReturnBatchInterface()
    {
        $batch = new TestableBatch($this->geocoder);
        $batchReturned = $batch->reverse($this->getStubCoordinate());

        $this->assertTrue(is_object($batchReturned));
        $this->assertInstanceOf('Geotools\Batch\Batch', $batchReturned);
        $this->assertInstanceOf('Geotools\Batch\BatchInterface', $batchReturned);
        $this->assertSame($batch, $batchReturned);
    }

    public function testReverseShouldMadeCorrectTasksArrayToCompute()
    {
        $batch = new TestableBatch($this->geocoder);
        $batch->reverse($this->getStubCoordinate());
        $tasks = $batch->getTasks();

        $this->assertTrue(is_array($tasks));
        $this->assertCount(3, $tasks);
    }

    public function testReverseShouldMadeCorrectTasksArrayToComputeWithManyCoordinates()
    {
        $batch = new TestableBatch($this->geocoder);
        $batch->reverse(array(
            $this->getStubCoordinate(),
            $this->getStubCoordinate(),
            $this->getStubCoordinate(),
        ));
        $tasks = $batch->getTasks();

        $this->assertTrue(is_array($tasks));
        $this->assertCount(9, $tasks);
    }

    /**
     * @expectedException Geotools\Exception\InvalidArgumentException
     * @expectedExceptionMessage The argument should be a Coordinate instance or an array of Coordinate instances to reverse.
     * @dataProvider coordinatesProvider
     */
    public function testReverseShouldThrowInvalidArgumentException($coordinates)
    {
        $batch = new TestableBatch($this->geocoder);
        $batch->reverse($coordinates);
    }

    public function coordinatesProvider()
    {
        return array(
            array(0),
            array(0.0),
            array(1234, 5678.0),
            array(array()),
            array(array(), array()),
            array(' '),
            array(' ', ' '),
        );
    }

    public function testBatchGeocodeInSerie()
    {
        $geocoder = $this->getMockGeocoderReturns($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInSerie = $batch->geocode('foo')->serie();

        $this->assertCount(count($this->providers), $resultComputedInSerie);
        foreach ($resultComputedInSerie as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEquals($this->data['latitude'], $providerResult->getLatitude());
            $this->assertEquals($this->data['longitude'], $providerResult->getLongitude());
        }
    }

    public function testBatchGeocodeInSerieWithManyValues()
    {
        $values = array(
            'foo',
            'bar',
            'baz',
        );
        $geocoder = $this->getMockGeocoderReturns($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInSerie = $batch->geocode($values)->serie();

        $this->assertCount(count($this->providers) * count($values), $resultComputedInSerie);
        foreach ($resultComputedInSerie as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEquals($this->data['latitude'], $providerResult->getLatitude());
            $this->assertEquals($this->data['longitude'], $providerResult->getLongitude());
        }
    }

    public function testBatchReverseGeocodingInSerie()
    {
        $geocoder = $this->getMockGeocoderReturns($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInSerie = $batch->reverse($this->getStubCoordinate())->serie();

        $this->assertCount(count($this->providers), $resultComputedInSerie);
        foreach ($resultComputedInSerie as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEquals($this->data['latitude'], $providerResult->getLatitude());
            $this->assertEquals($this->data['longitude'], $providerResult->getLongitude());
        }
    }

    public function testBatchReverseGeocodingInSerieWithManyCoordinates()
    {
        $coordinates = array(
            $this->getStubCoordinate(),
            $this->getStubCoordinate(),
            $this->getStubCoordinate(),
        );
        $geocoder = $this->getMockGeocoderReturns($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInSerie = $batch->reverse($coordinates)->serie();

        $this->assertCount(count($this->providers) * count($coordinates), $resultComputedInSerie);
        foreach ($resultComputedInSerie as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEquals($this->data['latitude'], $providerResult->getLatitude());
            $this->assertEquals($this->data['longitude'], $providerResult->getLongitude());
        }
    }

    public function testBatchGeocodeInParallel()
    {
        $geocoder = $this->getMockGeocoderReturns($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInParallel = $batch->geocode('foo')->parallel();

        $this->assertCount(count($this->providers), $resultComputedInParallel);
        foreach ($resultComputedInParallel as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEquals($this->data['latitude'], $providerResult->getLatitude());
            $this->assertEquals($this->data['longitude'], $providerResult->getLongitude());
        }
    }

    public function testBatchGeocodeInParallelWithManyValues()
    {
        $values = array(
            'foo',
            'bar',
            'baz',
        );
        $geocoder = $this->getMockGeocoderReturns($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInParallel = $batch->geocode($values)->parallel();

        $this->assertCount(count($this->providers) * count($values), $resultComputedInParallel);
        foreach ($resultComputedInParallel as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEquals($this->data['latitude'], $providerResult->getLatitude());
            $this->assertEquals($this->data['longitude'], $providerResult->getLongitude());
        }
    }

    public function testBatchReverseGeocodingInParallel()
    {
        $geocoder = $this->getMockGeocoderReturns($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInSerie = $batch->reverse($this->getStubCoordinate())->parallel();

        $this->assertCount(count($this->providers), $resultComputedInSerie);
        foreach ($resultComputedInSerie as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEquals($this->data['latitude'], $providerResult->getLatitude());
            $this->assertEquals($this->data['longitude'], $providerResult->getLongitude());
        }
    }

    public function testBatchReverseGeocodingInParallelWithManyCoordinates()
    {
        $coordinates = array(
            $this->getStubCoordinate(),
            $this->getStubCoordinate(),
            $this->getStubCoordinate(),
        );
        $geocoder = $this->getMockGeocoderReturns($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInSerie = $batch->reverse($coordinates)->parallel();

        $this->assertCount(count($this->providers) * count($coordinates), $resultComputedInSerie);
        foreach ($resultComputedInSerie as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEquals($this->data['latitude'], $providerResult->getLatitude());
            $this->assertEquals($this->data['longitude'], $providerResult->getLongitude());
        }
    }

    public function testBatchGeocodeInSerieOrParallelReturnNewGeocodedInstance()
    {
        $geocoder = $this->getMockGeocoderThrowException($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInSerie = $batch->geocode('foo')->serie();

        $this->assertCount(count($this->providers), $resultComputedInSerie);
        foreach ($resultComputedInSerie as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEmpty($providerResult->getLatitude());
            $this->assertEmpty($providerResult->getLongitude());
        }
    }

    public function testBatchGeocodeInSerieOrParallelReturnNewGeocodedInstanceWithManyValues()
    {
        $values = array(
            'foo',
            'bar',
            'baz',
        );
        $geocoder = $this->getMockGeocoderThrowException($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInSerie = $batch->geocode($values)->serie();

        $this->assertCount(count($this->providers) * count($values), $resultComputedInSerie);
        foreach ($resultComputedInSerie as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEmpty($providerResult->getLatitude());
            $this->assertEmpty($providerResult->getLongitude());
        }
    }

    public function testBatchReverseGeocodingInSerieOrParallelReturnNewGeocodedInstance()
    {
        $geocoder = $this->getMockGeocoderThrowException($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInSerie = $batch->reverse($this->getStubCoordinate())->serie();

        $this->assertCount(count($this->providers), $resultComputedInSerie);
        foreach ($resultComputedInSerie as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEmpty($providerResult->getLatitude());
            $this->assertEmpty($providerResult->getLongitude());
        }
    }

    public function testBatchReverseGeocodingInSerieOrParallelReturnNewGeocodedInstanceWithManyCoordinates()
    {
        $coordinates = array(
            $this->getStubCoordinate(),
            $this->getStubCoordinate(),
            $this->getStubCoordinate(),
        );
        $geocoder = $this->getMockGeocoderThrowException($this->providers, $this->data);
        $batch = new TestableBatch($geocoder);
        $resultComputedInSerie = $batch->reverse($coordinates)->serie();

        $this->assertCount(count($this->providers) * count($coordinates), $resultComputedInSerie);
        foreach ($resultComputedInSerie as $providerResult) {
            $this->assertTrue(is_object($providerResult));
            $this->assertInstanceOf('Geocoder\Result\Geocoded', $providerResult);
            $this->assertInstanceOf('Geocoder\Result\ResultInterface', $providerResult);
            $this->assertEmpty($providerResult->getLatitude());
            $this->assertEmpty($providerResult->getLongitude());
        }
    }
}

class TestableBatch extends Batch
{
    public function getGeocoder()
    {
        return $this->geocoder;
    }

    public function getTasks()
    {
        return $this->tasks;
    }
}

class MockProvider implements ProviderInterface
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getGeocodedData($address)
    {
        return array();
    }

    public function getReversedData(array $coordinates)
    {
        return array();
    }

    public function getName()
    {
        return $this->name;
    }
}
