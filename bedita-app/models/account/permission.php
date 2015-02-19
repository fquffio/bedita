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
 * Represents an Object's permissions.
 *
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $LastChangedDate$
 * 
 * $Id$
 */
class Permission extends BEAppModel
{
    const PERM_NOINHERIT = 1;
    const PERM_READ = 1;  // 2nd-3rd bit.
    const PERM_WRITE = 3;  // 4th-5th bit.

    const PERM_NO = 0;  // 0b00
    const PERM_NEVER = 1;  // 0b01
    const PERM_PARTIAL = 2;  // 0b10
    const PERM_FULL = 3;  // 0b11

    public $belongsTo = array(
        'User' => array(
            'className' => 'User',
            'conditions' => "Permission.switch = 'user' ",
            'foreignKey' => 'ugid'
        ),
        'Group' => array(
            'className' => 'Group',
            'conditions' => "Permission.switch = 'group' ",
            'foreignKey' => 'ugid'
        ),
    );

    /**
     * Returns an associative array of IDs / names of the Groups the User belongs to.
     *
     * @param array $userData User data array.
     * @return array Associative array of Groups the User belongs to.
     */
    private function getGroups (array $userData) {
        // Find Groups IDs.
        $groups = array();
        if (!is_null($userData)) {
            $groups = ClassRegistry::init('Group')->find('list', array(
                'field' => array('id', 'name'),
                'conditions' => array(
                    'OR' => array(
                        'name' => $userData['groups'],
                        'id' => $userData['groups'],
                    ),
                ),
            ));
        }
        $groups[0] = 'everyone';
        return $groups;
    }

    /**
     * Load all object's permissions, with inheritance, that apply to the passed User.
     *
     * @param string $path Path of object, as retreived from `Tree` model.
     * @param array $userData User data. If empty, retreives all permissions.
     * @return array Multidimensional array of inherited permissions, where first-level keys stand for inheritance depth level.
     */
    public function loadPath ($path, array $userData = null) {
        $path = array_reverse(explode('/', ltrim($path, '/')));  // Find parent objects' IDs.
        $conditions = array(
            'object_id' => $path,
        );

        // TODO: Use object cache.

        if (!empty($userData)) {
            $conditions['OR'] = array(
                'AND' => array('switch' => 'group', 'ugid' => array_keys($this->getGroups($userData))),
                'OR' => array(
                    '0 = 1',
                    'AND' => array('switch' => 'user', 'ugid' => $userData['id']),
                ),
            );
        }

        // Read permissions.
        $res = $this->find('all', array(
            'contain' => array(),
            'conditions' => $conditions,
        ));

        // Format with hierarchy.
        $perms = array();
        foreach ($res as $perm) {
            $key = array_search($perm['object_id'], $path);
            if (!array_key_exists($key, $perms)) {
                $perms = array();
            }
            array_push($perms[$key], $perm);
        }
        return $perms;
    }

    /**
     * Load all object's permissions, without inheritance, that apply to the passed User.
     *
     * @param int $objectId Object ID.
     * @param array $userData User's data. If empty, retreives all permissions.
     * @return array Object's permissions.
     * @see Permission::loadPath()
     */
    public function load ($objectId, array $userData = null) {
        $perms = $this->loadPath($objectId, $userData);
        return $perms[0];
    }

    /**
     * Passed an array of BEdita objects add 'num_of_permission' key
     * with the number of permissions applied to those objects.
     *
     * @param array $objects Objects to count permissions for.
     * @param array $options
     *               - flag: if specified count permission with that flag
     * @return array Objects array with added 'num_of_permission' key.
     */
    public function countPermissions (array $objects, array $options) {
        if (is_null($options)) {
            $options = array();
        }

        foreach ($objects as &$obj) {
            $conditions = array('object_id' => $obj['id']);
            if (array_key_exists('flag', $options)) {
                $conditions['flag'] = $options['flag'];
            }
            $obj['num_of_permission'] = $this->find('count', array(
                'conditions' => $conditions
            ));
        }
        return $objects;

        // TODO: execute a single query.
        $ids = Set::classicExtract($objects, '{n}.id');
        $conditions = array(
            'object_id' => $ids,
        );
        if (array_key_exists('flag', $options)) {
            $conditions['flag'] = $options['flag'];
        }
        $res = $this->find('list', array(
            'contain' => array(),
            'fields' => array('object_id', 'COUNT(ugid) AS num_of_permission'),
            'conditions' => $conditions,
            'group' => array('object_id'),
        ));
        foreach ($objects as &$obj) {
            $obj['num_of_permission'] = $res[$obj['id']];
        }
        return $objects;
    }

    /**
     * Formats a permission value to the standard format.
     *
     * @param mixed $value Value. Can be either one of the predefined `PERM_*` constants, or a string.
     * @return int Standardized value.
     */
    private function formatValue ($value) {
        if (is_numeric($value)) {
            return (int) $value & self::PERM_FULL;
        }
        switch (strtoupper($value)) {
            case 'NO':
                return self::PERM_NO;
            case 'NEVER':
                return self::PERM_NEVER;
            case 'PARTIAL':
                return self::PERM_PARTIAL;
            case 'FULL':
                return self::PERM_FULL;
        }
        return self::PERM_NO;
    }

    /**
     * Formats a flag according to the standard format, ensuring it is well formed.
     *
     * @param mixes $flag Flag. Can be either an `int`, or an array with keys `'read', 'write', 'noinherit'`.
     * @return int Standardized flag.
     */
    private function formatFlag ($flag) {
        if (is_int($flag)) {
            return $flag & ((self::PERM_FULL << self::PERM_WRITE) | (self::PERM_FULL << self::PERM_READ) | self::PERM_NOINHERIT);
        }
        if (is_array($flag)) {
            $res = 0;
            if (array_key_exists('read', $flag)) {
                $res = $res | ($this->formatValue($flag['read']) << self::PERM_READ);
            }
            if (array_key_exists('write', $flag)) {
                $res = $res | ($this->formatValue($flag['write']) << self::PERM_WRITE);
            }
            if (array_key_exists('noinherit', $flag)) {
                $res = $res | ((int) $flag['noinherit'] & self::PERM_NOINHERIT);
            }
            return $res;
        }
        return 0;
    }

    /**
     * Add permissions to an Object.
     *
     * @param integer $objectId Object ID.
     * @param array $perms Bidimensional array like
     *      ```
     *      array(
     *          array(
     *              'flag' => 1,
     *              'switch' => 'group',
     *              'name' => 'guest',
     *          ),
     *          array(
     *              'flag' => 31,
     *              'switch' => 'group',
     *              'ugid' => 2,
     *          ),
     *          ...
     *      )
     *      ```
     */
    public function add ($objectId, array $perms) {
        foreach ($perms as $p) {
            $p['object_id'] = $objectId;
            if (array_key_exists('name', $p)) {
                $group = ($p['switch'] == 'group');
                $p['ugid'] = ClassRegistry::init($group ? 'Group' : 'User')->field('id', array(
                    ($group ? 'name' : 'userid') => $p['name'],
                ));
                unset($p['name']);
            }

            $this->create();
            if (!$this->save($p)) {
                throw new BeditaException(__('Error saving permissions', true), "object id: {$objectId} - permissions: " . var_export($perms, true));
            }
        }
        return true;
    }

    /**
     * Add permissions for User/Group.
     *
     * @param integer $ugId User/Group ID.
     * @param array $perms Bidimensional array like
     *      ```
     *      array(
     *          array(
     *              'flag' => 1,
     *              'object_id' => 1,
     *          ),
     *          array(
     *              'flag' => 31,
     *              'nickname' => 'my-object',
     *          ),
     *          ...
     *      )
     *      ```
     * @param string $switch Type of permissions to add (either `'group'` or `'user'`).
     */
    public function addUG ($ugId, array $perms, $switch) {
        foreach ($perms as $p) {
            $p['ugid'] = $ugId;
            $p['switch'] = $switch;
            if (array_key_exists('nickname', $p)) {
                $p['object_id'] = ClassRegistry::init('BEObject')->field('id', array('nickname' => $p['nickname']));
                unset($p['nickname']);
            }

            $this->create();
            if (!$this->save($p)) {
                throw new BeditaException(__('Error saving permissions', true), "{$switch} id: {$ugId} - permissions: " . var_export($perms, true));
            }
        }
        return true;
    }

    /**
     * Delete a permission for an Object.
     *
     * @param integer $objectId Object ID.
     * @param array $perms Permissions array (see above).
     * @see Permission::add()
     */
    public function remove ($objectId, array $perms) {
        foreach ($perms as $p) {
            if (!array_key_exists('ugid', $p)) {
                $group = ($p['switch'] == 'group');
                $p['ugid'] = ClassRegistry::init($group ? 'Group' : 'User')->field('id', array(
                    ($group ? 'name' : 'userid') => $p['name'],
                ));
                unset($p['name']);
            }
            $conditions = array_merge($p, array(
                'object_id' => $objectId,
                'switch' => $p['switch'],
            ));
            if (!$this->deleteAll($conditions, false)) {
                throw new BeditaException(__('Error removing permissions', true), "object id: {$objectId}");
            }
        }
    }

    /**
     * Remove all Object's permissions.
     *
     * @param integer $objectId Object ID.
     * @param string $switch Type of permissions to remove (either `'group'` or `'user'`). If `null`, removes 'em all.
     */
    public function removeAll ($objectId, $switch = null) {
        $conditions = array(
            'object_id' => $objectId,
        );
        if (!empty($switch)) {
            $conditions['switch'] = $switch;
        }
        if (!$this->deleteAll($conditions, false)) {
            throw new BeditaException(__('Error removing permissions', true), "object id: {$objectId}");
        }
        return true;
    }

    /**
     * Remove all permissions for User/Group.
     *
     * @param integer $ugId User/Group ID.
     * @param string $switch Type of permissions to remove (either `'group'` or `'user'`).
     */
    public function removeAllUG ($ugId, $switch) {
        $conditions = array(
            'ugid' => $ugId,
            'switch' => $switch,
        );
        if (!$this->deleteAll($conditions, false)) {
            throw new BeditaException(__('Error removing permissions', true), "{$switch} id: {$ugId}");
        }
        return true;
    }

    /**
     * Updates/replaces an Object's permissions.
     *
     * @param integer $objectId Object ID.
     * @param array $perms Permissions array (see above).
     * @param string $switch Type of permissions to remove (either `'group'` or `'user'`). If `null`, removes 'em all.
     * @see Permission::add()
     */
    public function replace ($objectId, array $perms, $switch = null) {
        $this->removeAll($objectId, $switch);
        if (!is_null($switch)) {
            foreach ($perms as &$p) {
                $p['switch'] = $switch;
            }
        }
        return $this->add($objectId, $perms);
    }

    /**
     * Updates/replaces permissions for User/Group.
     *
     * @param integer $ugId User/Group ID.
     * @param array $perms Permissions array (see above).
     * @param string $switch Type of permissions to remove (either `'group'` or `'user'`). If `null`, removes 'em all.
     * @see Permission::addUG()
     */
    public function replaceUG ($ugId, array $perms, $switch) {
        $this->removeAllUG($ugId, $switch);
        foreach ($perms as &$p) {
            $p['ugid'] = $ugId;
            $p['switch'] = $switch;
        }
        return $this->addUG($ugId, $perms, $switch);
    }

    /**
     * Updates/replaces permissions for Group. Alias of `Permission::replaceUG()`.
     *
     * @param int $groupId Group ID.
     * @param array $perms Permissions array (see above).
     * @see Permission::replaceUG()
     * @deprecated
     */
    public function replaceGroupPerms ($groupId, array $perms) {
        return $this->replaceUG($groupId, $perms, 'group');
    }

    /**
     * Returns the permission resulting from the passed permission set.
     *
     * @param array $allPerms All permissions, as a tridimensional array, where first depth level represent inheritance depth level, and second level is an array of permissions (see above).
     * @param array $userData User data array containing key `id` and `groups`, as a list of Groups to be checked against. Group "everyone" with ID `0` is always taken into account.
     * @param mixed $type Permission type. Can be either a string (`'read'` or `'write'`) or one of the two constants `Permission::PERM_READ`, `Permission::PERM_WRITE`.
     * @return int Resulting permission, as one of the constants `PERM_NO`, `PERM_PARTIAL` or `PERM_FULL`.
     */
    public function checkPermissions (array $allPerms, array $userData = null, $type = self::PERM_READ) {
        $groups = $this->getGroups($userData);
        if (in_array('administrator', $groups)) {
            // Administrators always have full permissions.
            return self::PERM_FULL;
        }

        // Choose permission type.
        switch ($type) {
            case 'write':
            case self::PERM_WRITE:
                $type = self::PERM_WRITE;
                break;
            case 'read':
            case self::PERM_READ:
            default:
                $type = self::PERM_READ;
        }

        $perm = 0;
        $stopInheritance = 0;
        $applied; $flag;
        foreach ($allPerms as /*$depth => */$perms) {
            // Inheritance.
            foreach ($perms as $p) {
                $applied = is_null($userData);  // No user data passed (assumes already filtered permissions).
                $applied = $applied || ($p['switch'] == 'user' && $p['ugid'] == $userData['id']);  // User permission with matching User ID.
                $applied = $applied || ($p['switch'] == 'group' && array_key_exists($p['ugid'], $groups));  // Group permission with matching Group ID.
                if (!$applied) {
                    // Filter by actually applied permissions.
                    continue;
                }

                // Multiple groups.
                $stopInheritance = $stopInheritance | ($p['flag'] & self::PERM_NOINHERIT);
                $flag = ($p['flag'] >> $type) & self::PERM_FULL;
                if ($flag == self::PERM_NEVER) {
                    // Blocked.
                    return self::PERM_NO;
                }
                $perm = $perm | $flag;
            }
            if ($stopInheritance == self::PERM_NOINHERIT) {
                // Inheritance stopped.
                break;
            }
        }
        return $perm;
    }

    /**
     * Tells whether Object is writable by the User.
     *
     * @param int $objectId Object ID.
     * @param array $userData User data array.
     * @param array $perms Permission array as obtained from `Permission::loadPath()`.
     * @return boolean Writeable.
     * @see Permission::checkPermissions()
     * @deprecated
     */
    public function isWritable ($objectId, array $userData, array $perms = array()) {
        if (empty($perms)) {
            $perms = $this->loadPath($objectId, $userData);
        }
        return (bool) $this->checkPermissions($perms, $userData, self::PERM_WRITE);
    }

    /**
     * Tells whether Object is readable by the User.
     * 
     * @param array $perms Permission array as obtained from `Permission::loadPath()`.
     * @param array $userData User data array.
     * @return boolean Readable.
     * @see Permission::checkPermissions()
     * @deprecated
     */
    public function checkPermissionByUser (array $perms=array(), array &$userData) {
        return (bool) $this->checkPermissions($perms, $userData);
    }



    /**************** \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ \/ ****************/



	/**
	 * Is object ($objectId) forbidden to user?
	 * Backend only (check backend_private permission)
	 *
	 * @param integer $objectId
	 * @param array $userData user data, like array("id" => .., "userid" => ..., "groups" => array("administrator", "frontend",...))
	 * @return boolean, true if it's forbidden false if it's allowed
	 */
	public function isForbidden($objectId, array &$userData) {
		// no private objects for administrator
		if (!BACKEND_APP || ( !empty($userData['groups']) && in_array("administrator", $userData['groups'])) ) {
			return false;
		}

		$forbidden = false;
		$privatePermission = Configure::read("objectPermissions.backend_private");

		// check perms on main object ($objectId)
		$perms = $this->isPermissionSet($objectId, $privatePermission);
		$forbidden = !$this->checkPermissionByUser($perms, $userData);
		if ($forbidden) {
			return true;
		}

		// check if some branch parent is allowed, if so object is not forbidden
		$parentsPath = ClassRegistry::init('Tree')->find('list', array(
			'fields' => array('parent_path'),
			'conditions' => array('id' => $objectId)
		));

		if (!empty($parentsPath)) {
			foreach ($parentsPath as $path) {
				$path = trim($path, '/');
				$pathArr = explode('/', $path);
				$branchAllowed = array();
				foreach ($pathArr as $parentId) {
					$perms = $this->isPermissionSet($parentId, $privatePermission);
					$branchAllowed[] = $this->checkPermissionByUser($perms, $userData);
				}

				if (!in_array(false, $branchAllowed)) {
					$forbidden = false;
					break;
				} else {
					$forbidden = true;
				}
			}
		}

		return $forbidden;
	}

	/**
	 * Is object ($objectId) accessible by user in frontend?
	 * 
	 * @param $objectId
	 * @param $userData  user data, like array("id" => .., "userid" => ..., "groups" => array("administrator", "frontend",...))
	 * @param $perms permission array defined like in checkPermissionByUser() call
	 * 				 if it's defined use it else get permission by $objectId
	 * @return boolean, true if it's accessible
	 */
	public function isAccessibleByFrontend($objectId, array &$userData, $perms = array()) {
		if (empty($perms)) {
			$perms = $this->isPermissionSet($objectId, array(
				Configure::read("objectPermissions.frontend_access_with_block"),
				Configure::read("objectPermissions.frontend_access_without_block")
			));
		}
		return $this->checkPermissionByUser($perms, $userData);
	}
	
    /**
     * Return frontend level access to an object
     *
     * Possible returned values are:
     *
     * * 'free' if the object has not frontend_access perms
     * * 'denied' if the object isn't accessible (frontend_access_with_block perms set and user groups haven't that permission on that object)
     * * 'partial' if the object is accessible in preview (frontend_access_without_block perms set and user groups haven't that permission on that object)
     * * 'full' if the object has perms and user groups have that permission on that object
     *
     * @param int $objectId
     * @param array &$userData user data as
     *                         ```
     *                         array(
     *                             'id' => ..,
     *                             'userid' => ...,
     *                             'groups' => array('administrator', 'frontend',...)
     *                         )
     *                         ```
     * @return string
     */
	public function frontendAccess($objectId, array &$userData = array()) {
		$accessWithBlock = Configure::read('objectPermissions.frontend_access_with_block');
		$accessWithoutBlock = Configure::read('objectPermissions.frontend_access_without_block');
		$perms = $this->isPermissionSet($objectId, array($accessWithBlock, $accessWithoutBlock));

		// full access because no perms are set
		if (empty($perms)) {
			return 'free';
		}

		if (!empty($userData)) {
			// full access => one perm for user group
			if ($this->checkPermissionByUser($perms, $userData)) {
			    return 'full';
			}
		}

		$flags = Set::extract('/Permission/flag', $perms);

		// access denied => object has at least one perm 'frontend_access_with_block'
		if (in_array($accessWithBlock, $flags)) {
			return 'denied';
		}
		// partial access => object has at least one perm 'frontend_access_without_block'
		return 'partial';
	}

	/**
	 * Check if an Object has permissions of the given type.
	 *
	 * @param integer $objectId Object ID.
	 * @param array|integer $flag Permission flag.
	 * @return array|boolean Array of permissions set, or `false` if none are found.
	 */
	public function isPermissionSet ($objectId, $flag) {
		if (!is_array($flag)) {
			$flag = array($flag);
		}
		// if frontend app (not staging) and object cache is active
		if (!BACKEND_APP && Configure::read('objectCakeCache') && !Configure::read('staging')) {
			$beObjectCache = BeLib::getObject('BeObjectCache');
			$options = array();
			$perms = $beObjectCache->read($objectId, $options, 'perms');
			if (!$perms && !is_array($perms)) {
				$perms = $this->find('all', array(
					'conditions' => array('object_id' => $objectId)
				));
				$beObjectCache->write($objectId, $options, $perms, 'perms');
			}
			// search $flag inside $perms
			$result = array();
			if (!empty($perms)) {
				foreach ($perms as $p) {
					if (in_array($p['Permission']['flag'], $flag)) {
						$result[] = $p;
					}
				}
			}
		} else {
			$result = $this->find('all', array(
				'conditions' => array('object_id' => $objectId, 'flag' => $flag)
			));
		}

		$ret = (!empty($result)) ? $result : false;
		return $ret;
	}
}
