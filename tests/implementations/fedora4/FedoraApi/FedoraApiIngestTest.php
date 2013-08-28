<?php
/**
 * @file
 * A set of test classes that test the implementations/fedora3/FedoraApi.php file
 */

require_once 'implementations/fedora4/FedoraApi.php';
require_once 'implementations/fedora4/FedoraApiSerializer.php';
require_once 'tests/TestHelpers.php';

class FedoraApi4IngestTest extends PHPUnit_Framework_TestCase {
  protected $pids = array();
  protected $files = array();

  protected function setUp() {
    $this->connection = new RepositoryConnection(new RepositoryConfig('http://localhost:8080/rest'));
    $this->serializer = new Fedora4ApiSerializer();

    $this->apim = new Fedora4ApiM($this->connection, $this->serializer);
    $this->apia = new Fedora4ApiA($this->connection, $this->serializer);
  }

  protected function tearDown() {
    if (isset($this->pids) && is_array($this->pids)) {
      while ($pid = array_pop($this->pids)) {
        try {
          $this->apim->purgeObject($pid);
        }
        catch (RepositoryException $e) {}
      }
    }

    if (isset($this->files) && is_array($this->files)) {
      while ($file = array_pop($this->files)) {
        unlink($file);
      }
    }
  }

  public function testDescribeRepository() {
    $describe = $this->apia->describeRepository();
    $this->assertArrayHasKey('repositoryVersion', $describe);
    $this->assertEquals($describe['repositoryVersion'],'4.0.0');
  }
  
  public function testIngestNoPid() {

  }
  public function testGenerateDC() {
    $string = FedoraTestHelpers::randomString(10);
    $expected_pid = "test:$string";
    $result = $this->apim->connection->postRequest("/$expected_pid");
    $actual_pid = substr($result['content'], 1);
    $this->pid[] = $actual_pid;
    $dc_datastream = $this->apim->generateDC($actual_pid);
    $object =  $this->apia->getObjectProfile($actual_pid);
    $this->assertEquals($object['objLabel'],'Defualt Label');

  }
  public function testGenerateDCWithLabel() {
    $string = FedoraTestHelpers::randomString(10);
    $expected_pid = "test:$string";
    $result=$this->apim->connection->postRequest("/$expected_pid");
    $actual_pid = substr($result['content'], 1);
    $this->pid[]=$actual_pid;
    $label = FedoraTestHelpers::randomString(10);
    $dc_datastream = $this->apim->generateDC($actual_pid,$label);
    $object =  $this->apia->getObjectProfile($actual_pid);
    $this->assertEquals($object['objLabel'],$label);

  } 


  
  public function testIngestRandomPid() {
    $string = FedoraTestHelpers::randomString(10);
    $expected_pid = "test:$string";
    $actual_pid = $this->apim->ingest(array('pid' => $expected_pid));
    $this->pids[] = $actual_pid;
    $this->assertEquals($expected_pid, $actual_pid);
  }
  
  
  public function testIngestWithTransaction()
  {
    $string1 = FedoraTestHelpers::randomString(10);
    $expected_pid = "test:$string1";
    $txid = $this->apim->addTransaction();
    $actual_pid =  $this->apim->ingest(array('pid'=>"test:$string1",'txID'=>$txid));
    $this->apim->commitTransaction($txid);
    $object =  $this->apia->getObjectProfile($actual_pid);
    $this->assertEquals($object['objLabel'],'Defualt Label');
    
    $string2 = FedoraTestHelpers::randomString(10);
    $expected_pid2 = "test:$string2";
    $txid = $this->apim->addTransaction();
    $actual_pid2 =  $this->apim->ingest(array('pid'=>"test:$string2",'txID'=>$txid));
    echo $txid;
    $this->apim->rollbackTransaction($txid);
    $object2 =  $this->apia->getObjectProfile($actual_pid2);
    print_r($object2);
  }
/*
  public function testIngestLabel() {
    $string = FedoraTestHelpers::randomString(10);
    $pid = "test:$string";
    $expected_label = FedoraTestHelpers::randomString(15);
    $pid = $this->apim->ingest(array('pid' => $pid, 'label' => $expected_label));
    $this->pids[] = $pid;
    $results = $this->apia->findObjects('query', "pid=$pid", NULL, array('pid', 'label'));
    $this->assertEquals(1, count($results['results']));
    $this->assertEquals($pid, $results['results'][0]['pid']);
    $this->assertEquals($expected_label, $results['results'][0]['label']);
  }
*/
}