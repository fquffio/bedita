<?php
/*-----8<--------------------------------------------------------------------
 *
 * BEdita - a semantic content management framework
 *
 * Copyright 2008 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * BEdita is distributed WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * version 3 along with BEdita (see LICENSE.LGPL).
 * If not, see <http://gnu.org/licenses/lgpl-3.0.html>.
 *
 *------------------------------------------------------------------->8-----
 */

/**
 *
 *
 * @version			$Revision$
 * @modifiedby 		$LastChangedBy$
 * @lastmodified	$LastChangedDate$
 *
 * $Id$
 */
class Group extends BEAppModel {

	var $name = 'Group';

	var $validate = array(
		'name' => array(
	        'rule' => 'notEmpty'
		)
	);

	public $hasAndBelongsToMany = array("User");

	public function beforeDelete($cascade = true) {
	    #379 - temporary fix, not necessary ig groups_users.id is defined
	    $this->unbindModel( array('hasAndBelongsToMany' => array('User')), false);
	    return true;
	}

	public function afterDelete() {
	    #379 - temporary fix, not necessary ig groups_users.id is defined
	    $this->bindModel(array('hasAndBelongsToMany' => array('User')), false);
	    // remove all group premissions
	    $permission = ClassRegistry::init('Permission');
	    $del = $permission->deleteAll(array(
	    	'ugid' => $this->id,
	    	'switch' => 'group'
	    ));
	    if (!$del) {
	    	throw new BeditaException(__('Error deleting permission related to group to delete', true) . ' ' . $this->id);
	    }
	    return true;
	}

	function getList($conditions=array()) {
		$groups = $this->find("list", array(
				"conditions" => $conditions,
				"fields" => "name"
			)
		);
		return $groups;
	}

	/**
	 * return the number of users inside a group
	 *
	 * @param int $group_id
	 * @return int
	 */
	public function countUsersInGroup($group_id) {
		$users = $this->User->find("count", array(
			"joins" => array(
				array(
					'table' => 'groups_users',
					'alias' => 'GroupUser',
					'type' => 'inner',
					'conditions'=> array(
						'GroupUser.user_id = User.id',
						'GroupUser.group_id' => $group_id
					)
				)
			),
			'recursive' => -1
		));

		return $users;
	}

}
?>
