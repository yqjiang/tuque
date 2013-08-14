<?php

/**
 * @file
 * The RAW API wrappers for the Fedora interface.
 *
 * This file currently contains fairly raw wrappers around the Fedora REST
 * interface. These could also be reinmplemented to use for example the Fedora
 * SOAP interface. If there are version specific modifications to be made for
 * Fedora, this is the place to make them.
 */
set_include_path("sites/all/libraries/tuque/");
require_once 'RepositoryException.php';
require_once 'implementations/fedora3/FedoraApi.php';
require_once 'implementations/fedora3/RepositoryConnection.php';

/**
 * This is a simple class that brings FedoraApiM and FedoraApiA together.
 */
class Fedora4Api extends FedoraApi {

  /**
   * Fedora APIA Class
   * @var FedoraApiA
   */
  public $a;

  /**
   * Fedora APIM Class
   * @var FedoraApiM
   */
  public $m;

  /**
   *
   */
  public $connection;

  /**
   * Constructor for the FedoraApi object.
   *
   * @param RepositoryConnection $connection
   *   (Optional) If one isn't provided a default one will be used.
   * @param FedoraApiSerializer $serializer
   *   (Optional) If one isn't provided a default will be used.
   */
  public function __construct(RepositoryConnection $connection = NULL, Fedora4ApiSerializer $serializer = NULL) {
    if (!$connection) {
      $connection = new RepositoryConnection();
    }

    if (!$serializer) {
      $serializer = new FedoraApiSerializer();
    }

    $this->a = new Fedora4ApiA($connection, $serializer);
    $this->m = new Fedora4ApiM($connection, $serializer);

    $this->connection = $connection;
  }

}

/**
 * This class implements the Fedora API-A interface. This is a light wrapper
 * around the Fedora interface. Very little attempt is put into putting things
 * into native PHP datastructures.
 *
 * See this page for more information:
 * https://wiki.duraspace.org/display/FEDORA35/REST+API
 */
class Fedora4ApiA extends FedoraApiA {

  /**
   * Constructor for the new FedoraApiA object.
   *
   * @param RepositoryConnection $connection
   *   Takes the Respository Connection object for the Respository this API
   *   should connect to.
   * @param FedoraApiSerializer $serializer
   *   Takes the serializer object to that will be used to serialze the XML
   *   Fedora returns.
   */
  public function __construct(RepositoryConnection $connection, Fedora4ApiSerializer $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }

  /**
   * Does a simple call just to check if the repository is accessible.
   *
   * @return array
   *   Stubbed values for now.
   */
  public function describeRepository() {
    $options['headers'] = array('Accept: application/rdf+json');
    // Do the call just to test the connection nothing else.
    $response = $this->connection->getRequest("/", $options);
    return array(
      // Version number doesn't come down via any requests as of now.
      'repositoryVersion' => '4.0.0',
    );
  }

  /**
   * Not implemented.
   */
  public function findObjects($type, $query, $max_results = NULL, $display_fields = array('pid', 'title')) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

  /**
   * Not implemented.
   */
  public function resumeFindObjects($session_token) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

  /**
   * Gets the given datastream as of the given date time.
   *
   * Parameter $as_of_date_time no longer has any affect at the moment.
   * We will reintroduce it by doing two requests on to fcr:versions to get
   * the version ID and then another to getVersions for now just get the
   * latest.
   */
  public function getDatastreamDissemination($pid, $dsid, $as_of_date_time = NULL, $file = NULL) {
    //$pid = urlencode($pid);
    //$dsid = urlencode($dsid);
    $request = "/$pid/$dsid/fcr:content";
    $response = $this->connection->getRequest($request, array('file' => $file));
    $response = $this->serializer->getDatastreamDissemination($response, $file);
    return $response;
  }

  /**
   * Not implemented.
   */
  public function getDissemination($pid, $sdef_pid, $method, $method_parameters = NULL) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

  /**
   * Not implemented.
   */
  public function getObjectHistory($pid) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

  /**
   * Gets the given object as of the given date time.
   *
   * Parameter $as_of_date_time no longer has any affect at the moment.
   * We will reintroduce it by doing two requests on to fcr:versions to get
   * the version ID and then another to getVersions for now just get the
   * latest.
   */
  public function getObjectProfile($pid, $as_of_date_time = NULL) {
    //$pid = urlencode($pid);
    $request = "/{$pid}";
    $options['headers'] = array('Accept: application/rdf+json');
    $response = $this->connection->getRequest($request, $options);
    $response['content'] = json_decode($response['content'], TRUE);
    $id = $this->connection->buildUrl($request);
    $object = $this->serializer->getNode($id, $response['content']);
    //print_r($object);
    return array(
      // Object Labels are not implemented yet.
      'objLabel' => 'Default Label',
      'objOwnerId' => $object['createdBy'],
      // Content Models not implemented yet, will probably end up being
      // implemented as mixinType's.
      'objModels' => array('info:fedora/fedora-system:FedoraObject-3.0'),
      'objCreateDate' => $object['created'],
      'objLastModDate' => $object['lastModified'],
      // Object state not implemented yet.
      'objState' => 'A',
    );
  }

  /**
   * Gets the given object as of the given date time.
   *
   * Parameter $as_of_date_time no longer has any affect at the moment.
   * We will reintroduce it by doing two requests on to fcr:versions to get
   * the version ID and then another to getVersions for now just get the
   * latest.
   */
  public function listDatastreams($pid, $as_of_date_time = NULL) {
    //$pid = urlencode($pid);
    $request = "/{$pid}";
    $options['headers'] = array('Accept: application/rdf+json');
    $response = $this->connection->getRequest($request, $options);
    $response['content'] = json_decode($response['content'], TRUE);
    $id = $this->connection->buildUrl($request);
    //print_r($response['content']);
    $object = $this->serializer->getNode($id, $response['content']);
    $out = array();
    if (isset($object['datastreams'])) {
      foreach ($object['datastreams'] as $ds) {
        $out[$ds['id']] = array(
          'label' => 'Default Label',
          'mimetype' => $ds['content']['mimeType'],
        );
      }
    }
    return $out;
  }

  /**
   * Not implemented.
   */
  public function listMethods($pid, $sdef_pid = '', $as_of_date_time = NULL) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

}

/**
 * This class implements the Fedora API-M interface. This is a light wrapper
 * around the Fedora interface. Very little attempt is put into putting things
 * into native PHP datastructures.
 *
 * See this page for more information:
 * https://wiki.duraspace.org/display/FEDORA35/REST+API
 */
class Fedora4ApiM extends FedoraApiM {

  /**
   * Constructor for the new FedoraApiM object.
   *
   * @param RepositoryConnection $connection
   *   Takes the Respository Connection object for the Respository this API
   *   should connect to.
   * @param FedoraApiSerializer $serializer
   *   Takes the serializer object to that will be used to serialze the XML
   *   Fedora returns.
   */
  public function __construct(RepositoryConnection $connection, Fedora4ApiSerializer $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }

  /**
   * Add a datastream.
   *
   * Parameters that are no longer supported and will cause problems.
   * $type: When it equals 'url'
   * $params: 'dsLocation', 'controlGroup', 'altIDs', 'dsLabel', 'versionable',
   *          'dsState', 'formatURI', 'checksumType', 'checksum', 'mimeType',
   *          'logMessage'
   */
  public function addDatastream($pid, $dsid, $type, $file, $params) {
    //$pid = urlencode($pid);
    //$dsid = urlencode($dsid);
    $request = "/$pid/$dsid";

    $seperator = '?';
    switch (strtolower($type)) {
      case 'file':
      case 'string':
        break;
      default:
        throw new RepositoryBadArguementException("Type must be one of: file, string. ($type)");
        break;
    }
    //$this->connection->addParamArray($request, $seperator, $params, 'checksumType');
    //$this->connection->addParamArray($request, $seperator, $params, 'checksum');
    $this->connection->addParam($request, $seperator, 'mixin', 'fedora:datastream');
//    $response = $this->connection->postRequest($request, $type, $file, $params['mimeType']);
    $response = $this->connection->postRequest($request, $type, $file);
    // Second request to get info.
    //echo $response['headers'];
    return $this->getDatastream($pid, $dsid, null);
    //return $response['content'];
  }

  /**
   * Not implemented.
   *
   * @todo Implement.
   */
  public function addRelationship($pid, $relationship, $is_literal, $datatype = NULL) {
    trigger_error("TODO Implement this function.", E_USER_NOTICE);
  }

  /**
   * Not implemented.
   *
   * No support for FOXML at the moment.
   */
  public function export($pid, $params = array()) {
    $request = "/$pid/fcr:export";
    $seperator = '?';
    if (isset($params['exportFormat'])) {
      $this->connection->addParamArray($request, $seperator, $params['exportFormat'], 'fornat');
    }
    $response = $this->connection->getRequest($request);
    $return = $this->serializer->export($response);
    return $return;
  }

  /**
   * See add Datastream for what is stubbed etc.
   */
  public function getDatastream($pid, $dsid, $params = array()) {
    //$pid = urlencode($pid);
    //$dsid = urlencode($dsid);
    $request = "/{$pid}/$dsid";
    //echo $request;
    $options['headers_only'] = TRUE;
    $options['headers'] = array('Accept: application/rdf+json');
    $response = $this->connection->getRequest($request, $options);
    //echo "response:".$response['content']."\n";
    $responseArray = json_decode($response['content'], TRUE);
    $id = $this->connection->buildUrl($request);
    $id = str_replace('%3A', ':', $id);
    //echo $id."\n";
    // print_r($responseArray);
    $ds = $this->serializer->getNode($id, $responseArray);
    //print_r($ds);
    return array(
      // Datastream Labels are not implemented yet.
      'dsLabel' => 'Default Label',
      // Versioning doesn't work currently.
      'dsVersionID' => "$dsid.0",
      'dsCreateDate' => $ds['created'],
      // Datastream state not implemented yet.
      'dsState' => 'A',
      //'dsMIME' => $ds['content']['mimeType'],
      // Format URI is no longer supported.
      'dsFormatURI' => '',
      // Control Group is no longer supported.
      'dsControlGroup' => 'M',
      //'dsSize' => $ds['content']['size'],
      // Versioning doesn't work currently.
      'dsVersionable' => false,
      // dsInfoType, dsLocationType, and dsLocation are not relevent at the
      // moment.
      'dsInfoType' => '',
      'dsLocation' => '',
      'dsLocationType' => '',
      // Not provied by the request just assuming the call had the correct
      // values for now.
//      'dsChecksumType' => $params['checksumType'],
//      'dsChecksum' => $params['checksum'],
      // No message system for versions at the moment.
      'dsLogMessage' => '',
    );
  }

  /**
   * Versioning is borken at the moment.
   */
  public function getDatastreamHistory($pid, $dsid) {
    return array(
      $this->getDataststream($pid, $dsid)
    );
  }

  /**
   * Significantly different than before, since PIDS are now hierarchical.
   *
   * Added $pid as the last parameter, but made it a requirement so I could
   * catch the locations in which it was being used.
   *
   * Namespaces are no longer supported, perhaps we can use workspaces going
   * forward.
   */
  public function getNextPid($namespace = NULL, $numpids = NULL, $pid = NULL) {
    $request = isset($pid) ? "/$pid/fcr:pid" : '/fcr:pid';
    $seperator = '?';
    $id = $this->connection->buildUrl($request);
    $this->connection->addParam($request, $seperator, 'numPids', $numpids);
    $options = array(
      'headers' => array('Accept: application/rdf+json'),
    );
    $response = $this->connection->postRequest($request, 'none', NULL, NULL, $options);
    print_r($response);
    $response['content'] = json_decode($response['content'], TRUE);
    print_r($response['content']);
    $new_pids = $response['content'][$id]['info:fedora/fedora-system:def/internal#hasMember'];
    print_r($new_pids);
    foreach ($new_pids as &$pid) {
      $pid = preg_replace('/^.*\/rest\/(.*)$/', '$1', $pid['value']);
    }
    return count($new_pids) == 1 ? $new_pids[0] : $new_pids;
  }

  /**
   * FOXML is not implemented yet.
   */
  public function getObjectXml($pid) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("TODO implement.", E_USER_NOTICE);
  }

  /**
   * Will implement as jcr:properties?
   */
  public function getRelationships($pid, $relationship = array()) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("TODO implement.", E_USER_NOTICE);
  }

  /**
   * We should make this work with transactions.
   *
   * Parameter problems:
   *   - label: is ignored.
   *   - format: is ignored, assumed to be info:fedora/fedora-system:FOXML-1.1.
   *   - encoding: is ignored.
   *   - namespace: is ignored, namespaces are no longer relevent.
   *   - ownerId: also ignored until we implement some sorta filter.
   *   - logMessage: is ignored.
   */
  public function ingest($params = array()) {
    // Process the parameters
    if (isset($params['string'])) {
      $foxml = new SimpleXMLElement($params['string']);
    }
    elseif (isset($params['file'])) {
      $foxml = simplexml_load_file($params['file']);
    }
    $datastreams = array();
    if (isset($foxml)) {
      $pid = (string) $foxml['PID'];
      $children = $foxml->children('foxml', TRUE);
      $attribute = function(SimpleXMLElement $el, $attr) {
            $results = $el->xpath("@{$attr}");
            return (string) array_shift($results);
          };
      foreach ($children->datastream as $datastream) {
        $dsid = $attribute($datastream, 'ID');
        // Just grab the first version for now.
        $version = $datastream->datastreamVersion[0];
        $type = 'none';
        $data = NULL;
        if (isset($version->contentLocation)) {
          $type = 'file';
          $data = $attribute($version->contentLocation, 'REF');
        }
        elseif (isset($version->xmlContent)) {
          $type = 'string';
          $children = $version->xmlContent->xpath('*');
          $data = (string) array_pop($children)->asXML();
        }
        elseif (isset($version->binaryContent)) {
          $type = 'string';
          $data = (string) $version->binaryContent;
        }
        $datastreams[$dsid] = array(
          'mimeType' => $attribute($version, 'MIMETYPE'),
          'type' => $type,
          'data' => $data,
        );
      }
    }
    if (empty($pid)) {
      $pid = isset($params['pid']) ? $params['pid'] : '';
    }
    // Create the object.
    $request = empty($pid) ? "/fcr:new" : "/$pid";
    if (isset($params['txID'])) {
      $request = "/tx:" . $params['txID'] . $request;
    }
    $response = $this->connection->postRequest($request);
    $pid = $response['content'];
    $pid = substr($pid, 1);
    foreach ($datastreams as $dsid => $ds) {
      $params = array();
      $this->addDatastream($pid, $dsid, $ds['type'], $ds['data'], $params);
    }
    return $response['content'];
  }

  /**
   * 
   * @return s
   */
  public function addTransaction() {
    //post transactions
    $request = "/fcr:tx";
    $result = $this->connection->postRequest($request, 'none');
    //get transaction ID
    $headers = $result['headers'];
    $txstart = strpos($headers, 'tx:') + 3;
    $txId = substr($headers, $txstart, 36);
    return $txId;
  }

  public function commitTransaction($transactionID) {
    $request = "/tx:{$transactionID}/fcr:tx/fcr:commit";
    $this->connection->postRequest($request, 'none');
  }

  public function rollbackTransaction($transactionID) {
    $request = "/tx:{$transactionID}/fcr:tx/fcr:rollback";
    $this->connection->postRequest($request, 'none');
  }

  /**
   * Properties aside from content are just ignored for the moment.
   *
   * No longer sends up the lastModifiedDate, so we don't not if the content
   * has changed since we last retrieved it.
   */
  public function modifyDatastream($pid, $dsid, $params = array()) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    $request = "/{$pid}/{$dsid}";
    $seperator = '?';
    $options = array(
      'headers' => array("Content-Type: {$params['mimeType']}"),
    );
    // Setup the file.
    if (isset($params['dsFile'])) {
      $type = 'file';
      $data = $params['dsFile'];
    }
    elseif (isset($params['dsString'])) {
      $type = 'string';
      $data = $params['dsString'];
    }
    elseif (isset($params['dsLocation'])) {
      throw new RepositoryBadArguementException("dsLocation is no longer supported");
    }
    else {
      $type = 'none';
      $data = NULL;
    }
    $response = $this->connection->putRequest($request, $type, $data, $options);
    return $this->getDatastream($pid, $dsid);
  }

  /**
   * Not implemented.
   */
  public function modifyObject($pid, $params = NULL) {
    trigger_error("TODO implement.", E_USER_NOTICE);
  }

  /**
   * No longer returns the timestamps of the purged datastreams.
   */
  public function purgeDatastream($pid, $dsid, $params = array()) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    $request = "/{$pid}/{$dsid}";
    $this->connection->deleteRequest($request);
    // @todo The returned timestamps don't seem to ever get used, so we'll
    // ignore them for now.
  }

  /**
   * No longer returns the timestamps of the purged object.
   */
  public function purgeObject($pid, $log_message = NULL) {
    $pid = urlencode($pid);
    $request = "/{$pid}";
    $this->connection->deleteRequest($request);
    // @todo The returned timestamps don't seem to ever get used, so we'll
    // ignore them for now.
  }

  /**
   * Not implemented.
   */
  public function validate($pid, $as_of_date_time = NULL) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

  /**
   * This is significantly different as it doesn't exist anymore.
   *
   * But to stub it we return a url to the file on disc so that we can use the
   * same code.
   */
  public function upload($file) {
    $temp = tempnam(sys_get_temp_dir(), 'tuque');
    copy($file, $temp);
    return $temp;
  }

}
