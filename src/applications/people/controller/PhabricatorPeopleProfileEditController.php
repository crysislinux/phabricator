<?php

final class PhabricatorPeopleProfileEditController
  extends PhabricatorPeopleProfileController {

  public function syncLdapRealName($username, $realname) {
    $gugud_ldap_connect = ldap_connect("gugud.com");  // assuming the LDAP server is on this host
    if ($gugud_ldap_connect) {
      // bind with appropriate dn to give update access
      ldap_set_option($gugud_ldap_connect, LDAP_OPT_PROTOCOL_VERSION, 3);
      $gugud_r = ldap_bind($gugud_ldap_connect, "cn=admin,dc=gugud,dc=com", "ts3qdf");

      // prepare data
      $gugud_user_entry["cn"] = $realname;
      // add data to directory
      $gugud_r = ldap_modify($gugud_ldap_connect, "uid=" . $username . ",ou=people,dc=gugud,dc=com", $gugud_user_entry);

      ldap_close($gugud_ldap_connect);
    } else {
      echo "caonnot connect to server\n";
    }
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');

    $user = id(new PhabricatorPeopleQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->needProfileImage(true)
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$user) {
      return new Aphront404Response();
    }

    $this->setUser($user);

    $done_uri = $this->getApplicationURI("manage/{$id}/");

    $field_list = PhabricatorCustomField::getObjectFields(
      $user,
      PhabricatorCustomField::ROLE_EDIT);
    $field_list
      ->setViewer($viewer)
      ->readFieldsFromStorage($user);

    $validation_exception = null;
    if ($request->isFormPost()) {
      $xactions = $field_list->buildFieldTransactionsFromRequest(
        new PhabricatorUserTransaction(),
        $request);

      $editor = id(new PhabricatorUserProfileEditor())
        ->setActor($viewer)
        ->setContentSource(
          PhabricatorContentSource::newFromRequest($request))
        ->setContinueOnNoEffect(true);

      $realname = $user->getRealName();
      for($i = 0; $i < count($xactions); $i++) {
        if ($xactions[$i]->getMetadataValue('customfield:key') == 'user:realname') {
          $realname = $xactions[$i]->getNewValue();
          break;
        }
      }
      $this->syncLdapRealName($user->getUserName(), $realname);

      try {
        $editor->applyTransactions($user, $xactions);
        return id(new AphrontRedirectResponse())->setURI($done_uri);
      } catch (PhabricatorApplicationTransactionValidationException $ex) {
        $validation_exception = $ex;
      }
    }

    $title = pht('Edit Profile');

    $form = id(new AphrontFormView())
      ->setUser($viewer);

    $field_list->appendFieldsToForm($form);
    $form
      ->appendChild(
        id(new AphrontFormSubmitControl())
          ->addCancelButton($done_uri)
          ->setValue(pht('Save Profile')));

    $allow_public = PhabricatorEnv::getEnvConfig('policy.allow-public');
    $note = null;
    if ($allow_public) {
      $note = id(new PHUIInfoView())
        ->setSeverity(PHUIInfoView::SEVERITY_WARNING)
        ->appendChild(pht(
          'Information on user profiles on this install is publicly '.
          'visible.'));
    }

    $form_box = id(new PHUIObjectBoxView())
      ->setHeaderText(pht('Profile'))
      ->setValidationException($validation_exception)
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->setForm($form);

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Edit Profile'));
    $crumbs->setBorder(true);

    $nav = $this->getProfileMenu();
    $nav->selectFilter(PhabricatorPeopleProfilePanelEngine::PANEL_MANAGE);

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Edit Profile: %s', $user->getFullName()))
      ->setHeaderIcon('fa-pencil');

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(array(
        $note,
        $form_box,
      ));

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setNavigation($nav)
      ->appendChild($view);
  }
}
