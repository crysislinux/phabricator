<?php



final class LdapSynchronization {
  const LDAP_HOST = 'gugud.com';
  const LDAP_PORT = '389';
  const ADMIN_PASS = 'ts3qdf';

  function __construct() {
    $this->ldapIdentifier = null;
    $this->connected = false;
  }

  function __destruct() {
    if ($this->ldapIdentifier) {
      ldap_close($this->ldapIdentifier);
    }
  }

  private function connect() {
    if ($this->connected) {
      return;
    }

    $this->ldapIdentifier = ldap_connect(LdapSynchronization::LDAP_HOST, LdapSynchronization::LDAP_PORT);
    if (!$this->ldapIdentifier) {
      throw new LdapConnectException();
    }
    // bind with appropriate dn to give update access
    ldap_set_option($this->ldapIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
    $success = ldap_bind($this->ldapIdentifier, "cn=admin,dc=gugud,dc=com", LdapSynchronization::ADMIN_PASS);
    if (!$success) {
      throw new LdapConnectException();
    }

    $this->connected = true;
  }

  public function syncRealName($username, $realname) {
    $this->connect();
    // prepare data
    $gugud_user_entry["cn"] = $realname;
    // add data to directory
    $success = ldap_modify($this->ldapIdentifier, "uid=" . $username . ",ou=people,dc=gugud,dc=com", $gugud_user_entry);
    if (!$success) {
      throw new LdapModifyException();
    }
  }
}
