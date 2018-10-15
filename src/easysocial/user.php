<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_trading
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.user.user');
jimport('joomla.plugin.plugin');
jimport('joomla.user.helper');
jimport('joomla.application.component.helper');
jimport('joomla.application.component.model');
jimport('joomla.database.table.user');

require_once JPATH_SITE . '/libraries/joomla/filesystem/folder.php';
require_once JPATH_ROOT . '/administrator/components/com_users/models/users.php';

/**
 * User Api.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_api
 *
 * @since       1.0
 */
class EasysocialApiResourceUser extends ApiResource
{
	/**	  
	 * Function for get
	 * 	 
	 * @return  JSON
	 */
	public function get()
	{
	$this->plugin->setResponse("Use method post");
	}

	/**
	 * Function for post appname and organization content
	 * 	 
	 * @return  JSON
	 */
	public function post()
	{
		$app = JFactory::getApplication();
		$userIdentifier = $app->input->get('id', 0, 'String');

		if ($userIdentifier)
		{
			$response = new stdClass;
			$formData = $app->input->getArray();

			if ($formData['username'] == '' || $formData['name'] == '' || $formData['email'] == '')
			{
				ApiError::raiseError(400, JText::_('PLG_API_USERS_REQUIRED_DATA_EMPTY_MESSAGE'));
			}

				$user = JFactory::getUser($userIdentifier);

			if (!empty($userId))
			{
				$user = JFactory::getUser($userId);
			}

			$my = JFactory::getUser(606);

			// If user is already present then update it according to access.
			if (!empty($user->id))
			{
				$iAmSuperAdmin	= $my->authorise('core.admin');

				// Check if regular user is tring to update himself.
				if ($my->id == $user->id || $iAmSuperAdmin)
				{
					// If present then update or else dont include.
					if (!empty($formData['password']))
					{
						$formData['password2'] = $formData['password'];
					}

					// Add newly added groups and keep the old one as it is.
					if (!empty($formData['groups']))
					{
						$passedUserGroups['groups'] = array_unique(array_merge($user->groups, $formData['groups']));
					}

					$response = $this->storeUser($user, $formData);
					$this->plugin->setResponse($response);
				}
				else
				{
					ApiError::raiseError(400, JText::_('JERROR_ALERTNOAUTHOR'));
				}
			}
		}
		else
		{
			$this->plugin->setResponse($this->content());
		}
	}

	/**
	 * get videos throught api
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function content()
	{
		$res = new stdClass;
		$jinput = JFactory::getApplication();
		$app = $jinput->input->get('appname', null, 'STRING');
		$company = $jinput->input->get('company', 'Appcarvers', 'STRING');
		$day = $jinput->input->get('day', 30, 'INT');
		/* $res->message = "<h3>" .
						JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_ONE', $company) .
						JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_TWO', $app, $company) .
						JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_THREE', $company) .
						JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_FOUR', $company, $day) . "</h3>";
		*/

		$res->message = JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_ONE', $company);
		$res->message_one = JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_TWO', $app, $company);
		$res->subtitle = JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_THREE', $company);
		$res->rules = JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_FOUR', $company, $day);

		return $res;
	}

	/**
	 * Funtion for bind and save data and return response.
	 *
	 * @param   Object   $user      The user object.
	 * @param   Array    $formData  Array of user data to be added or updated.
	 * @param   Boolean  $isNew     Flag to differentiate the update of create action.
	 *
	 * @return  object|void  $response  the response object created on after user saving. void and raise error
	 *
	 * @since   2.0
	 */
	private function storeUser($user, $formData, $isNew = 0)
	{
		$response = new stdClass;
		$user = JFactory::getUser($userIdentifier);
		$user->bind($formData);
		$response->id = $user->id;
		$response = $this->createEsprofile($user->id, $user);

		return $response;
	}

	/**
	 * Function create easysocial profile.
	 *
	 * @param   Object  $log_user  The login user id object.
	 * @param   Array   $user      object of user data to be updated.
	 * 
	 * @return user obj
	 */
	public function createEsprofile($log_user, $user)
	{
		$obj = new stdClass;

		if (JComponentHelper::isEnabled('com_easysocial', true))
		{
			$app = JFactory::getApplication();

			$epost = $app->input->get('fields', '', 'ARRAY');
			$my = FD::user($log_user);

			require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';

			// Get all published fields apps that are available in the current form to perform validations
			$fieldsModel = FD::model('Fields');

			// Get current user.
			// Only fetch relevant fields for this user.
			$options = array(
					'profile_id' => $my->getProfile()->id,
					'data' => true, 'dataId' => $my->id,
					'dataType' => SOCIAL_TYPE_USER,
					'visible' => SOCIAL_PROFILES_VIEW_EDIT, 'group' => SOCIAL_FIELDS_GROUP_USER );

			$fields = $fieldsModel->getCustomFields($options);

			$epost = $this->create_field_arr($fields, $epost);

			// Load json library.
			$json = FD::json();

			// Initialize default registry
			$registry = FD::registry();

			// Get disallowed keys so we wont get wrong values.
			$disallowed = array( FD::token() , 'option' , 'task' , 'controller' );

			foreach ($epost as $key => $value)
			{
				if (!in_array($key, $disallowed))
				{
						if (is_array($value) && $key != 'es-fields-11')
						{
							$value  = $json->encode($value);
						}

					$registry->set($key, $value);
				}
			}

			// Convert the values into an array.
			$data = $registry->toArray();

			if (!$user->bind($data))
			{
				ApiError::raiseError(400, $my->getError());
			}

			$args 		= array(&$data, &$my);
			$fieldsLib	= FD::fields();

			$handler = $fieldsLib->getHandler();
			$fieldsLib->trigger('onRegisterAfterSave', SOCIAL_FIELDS_GROUP_USER, $fields, $args);

			$my->bindCustomFields($data);

			$args = array(&$data, &$my);

			$fieldsLib->trigger('onEditAfterSaveFields', SOCIAL_FIELDS_GROUP_USER, $fields, $args);

			if (!$user->save())
			{
				$obj->message = JText::_('PLG_API_EASYSOCIAL_UNABLE_UPDATED_PROFILE_MESSAGE');
			}
			else
			{
				$obj->message = JText::_('PLG_API_EASYSOCIAL_ACCOUNT_UPDATED_SUCCESSFULLY_MESSAGE');
			}

			return $obj;
		}
	}

	/**
	 * Function ield array as per easysocial
	 *
	 * @param   Object  $fields  The fields object.
	 * @param   Array   $post    Array of user data to be added or updated.
	 * 
	 * @return user obj
	 */
	public function create_field_arr($fields, $post)
	{
		$fld_data = array();
		$app = JFactory::getApplication();

		require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';

		// For upload photo

		if (!empty($_FILES['avatar']['name']))
		{
			$upload_obj = new EasySocialApiUploadHelper;

			$phto_obj = $upload_obj->ajax_avatar($_FILES['avatar']);
			$avtar_pth = $phto_obj['temp_path'];
			$avtar_scr = $phto_obj['temp_uri'];
			$avtar_typ = 'upload';
			$avatar_file_name = $_FILES['avatar']['name'];
		}

		foreach ($fields as $field)
		{
			$fobj = new stdClass;
			$fullname = $app->input->get('name', '', 'STRING');
			$fld_data['first_name'] = $app->input->get('name', '', 'STRING');

			$fobj->first = $fld_data['first_name'];
			$fobj->middle = '';
			$fobj->last = '';
			$fobj->name = $fullname;

			switch ($field->unique_key)
			{
				case 'JOOMLA_FULLNAME': $fld_data['es-fields-' . $field->id] = $fobj;
								break;
				case 'JOOMLA_USERNAME': $fld_data['es-fields-' . $field->id] = $app->input->get('username', '', 'STRING');
								break;
				case 'JOOMLA_PASSWORD': $fld_data['es-fields-' . $field->id] = $app->input->get('password', '', 'STRING');
								break;
				case 'JOOMLA_EMAIL': $fld_data['es-fields-' . $field->id] = $app->input->get('email', '', 'STRING');
								break;
				case 'AVATAR':
							if (isset($avtar_scr))
							{
								$fld_data['es-fields-' . $field->id] = Array
								(
									'source' => $avtar_scr,
									'path' => $avtar_pth,
									'data' => '',
									'type' => $avtar_typ,
									'name' => $avatar_file_name
								);
							}
							break;
			}
		}

		return $fld_data;
	}
}