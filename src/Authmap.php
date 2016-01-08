<?php

/**
 * @file
 * Contains Drupal\openid_connect\Authmap.
 */

namespace Drupal\openid_connect;

use Drupal\Core\Database\Connection;

/**
 * Class Authmap.
 *
 * @package Drupal\openid_connect
 */
class Authmap {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a Authmap object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection.
   */
  public function __construct(
    Connection $connection
  ) {
    $this->connection = $connection;
  }

  /**
   * Create a local to remote account association.
   *
   * @param object $account
   *   A user account object.
   * @param string $client_name
   *   The client name.
   * @param string $sub
   *   The remote subject identifier.
   */
  public function createAssociation($account, $client_name, $sub) {
    $fields = array(
      'uid' => $account->id(),
      'client_name' => $client_name,
      'sub' => $sub,
    );
    $this->connection->insert('openid_connect_authmap')
      ->fields($fields)
      ->execute();
  }

  /**
   * Deletes a user's authmap entries.
   *
   * @param int $uid
   *   A user id.
   * @param string $client_name
   *   A client name.
   */
  public function deleteAssociation($uid, $client_name = '') {
    $query = $this->connection->delete('openid_connect_authmap')
      ->condition('uid', $uid);
    if (!empty($client_name)) {
      $query->condition('client_name', $client_name, '=');
    }
    $query->execute();
  }

  /**
   * Loads a user based on a sub-id and a login provider.
   *
   * @param string $sub
   *   The remote subject identifier.
   * @param string $client_name
   *   The client name.
   *
   * @return object|bool
   *   A user account object or FALSE
   */
  public function userLoadBySub($sub, $client_name) {
    $result = $this->connection->select('openid_connect_authmap', 'a')
      ->fields('a', array('uid'))
      ->condition('client_name', $client_name, '=')
      ->condition('sub', $sub, '=')
      ->execute()
      ->fetchAssoc();
    if ($result) {
      $account = user_load($result['uid']);
      if (is_object($account)) {
        return $account;
      }
    }
    return FALSE;
  }

  /**
   * Get a list of external OIDC accounts connected to this Drupal account.
   *
   * @param object $account
   *   A Drupal user entity.
   *
   * @return array
   *   An array of 'sub' properties keyed by the client name.
   */
  public function getConnectedAccounts($account) {
    $auth_maps = $this->connection->select('openid_connect_authmap', 'a')
      ->fields('a', array('client_name', 'sub'))
      ->condition('uid', $account->id())
      ->execute();
    $results = array();
    foreach ($auth_maps as $auth_map) {
      $client = $auth_map->client_name;
      $sub = $auth_map->sub;
      $results[$client] = $sub;
    }
    return $results;
  }

}
