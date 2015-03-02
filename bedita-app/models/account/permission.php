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
    const PERM_READ = 1;  // 2nd-3rd bit.
    const PERM_WRITE = 3;  // 4th-5th bit.
    const PERM_BACKEND = 5;  // 6th-7th bit.

    const PERM_NO = 0;  // 0b00
    const PERM_NEVER = 1;  // 0b01
    const PERM_PARTIAL = 2;  // 0b10
    const PERM_FULL = 3;  // 0b11

    const PERM_NOINHERIT = 1;  // Inheritance stopper.

    public $belongsTo = array(
        'User' => array(
            'className' => 'User',
            'conditions' => array('Permission.switch' => 'user'),
            'foreignKey' => 'ugid'
        ),
        'Group' => array(
            'className' => 'Group',
            'conditions' => array('Permission.switch' => 'group'),
            'foreignKey' => 'ugid'
        ),
    );

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
            return $flag & ((self::PERM_FULL << self::PERM_BACKEND) | (self::PERM_FULL << self::PERM_WRITE) | (self::PERM_FULL << self::PERM_READ) | self::PERM_NOINHERIT);
        }
        if (is_array($flag)) {
            $res = 0;
            if (array_key_exists('read', $flag)) {
                $res = $res | ($this->formatValue($flag['read']) << self::PERM_READ);
            }
            if (array_key_exists('write', $flag)) {
                $res = $res | ($this->formatValue($flag['write']) << self::PERM_WRITE);
            }
            if (array_key_exists('backend', $flag)) {
                $res = $res | ($this->formatValue($flag['backend']) << self::PERM_BACKEND);
            }
            if (array_key_exists('noinherit', $flag)) {
                $res = $res | ((int) $flag['noinherit'] & self::PERM_NOINHERIT);
            }
            return $res;
        }
        return 0;
    }

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
     * Load all object's permissions, with inheritance, that apply to the passed User, with the given `flags`.
     *
     * @param string $path Path of object, as retreived from `Tree` model.
     * @param array $userData User data. If empty, retreives all permissions.
     * @param int $flags Flags to search for.
     * @return array Multidimensional array of inherited permissions, where first-level keys stand for inheritance depth level.
     */
    public function loadPath ($path, array $userData = null, $flags = null) {
        $path = array_reverse(explode('/', ltrim($path, '/')));  // Find parent objects' IDs.
        $groups = !empty($userData) ? array_keys($this->getGroups($userData)) : null;  // Get user groups.

        $perms = array();
        if (!BACKEND_APP && Configure::read('objectCakeCache') && !Configure::read('staging')) {
            // Use objects cache.
            $beObjectCache = BeLib::getObject('BeObjectCache');
            foreach ($path as $i => $oid) {
                // Read objects permissions.
                $res = $beObjectCache->read($oid, array(), 'perms');
                if (!$res && !is_array($res)) {
                    // Write object's permissions to cache.
                    $res = $this->find('all', array(
                        'conditions' => array('object_id' => $oid),
                    ));
                    $beObjectCache->write($oid, array(), $res, 'perms');
                }

                // Filter permissions.
                $perms[$i] = array();
                foreach ($res as $r) {
                    if (!empty($userData) && !($r['switch'] == 'group' && in_array($r['ugid'], $groups)) && !($r['switch'] == 'user' && $r['ugid'] == $userData['id'])) {
                        continue;
                    }
                    if (!empty($flags) && !in_array($r['flag'], $flags)) {
                        continue;
                    }
                    array_push($perms[$i], $r);
                }
            }
        } else {
            // Read from DB.
            $conditions = array(
                'object_id' => $path,
            );
            if (!empty($userData)) {
                $conditions['OR'] = array(
                    'AND' => array('switch' => 'group', 'ugid' => $groups),
                    'OR' => array(
                        '0 = 1',
                        'AND' => array('switch' => 'user', 'ugid' => $userData['id']),
                    ),
                );
            }
            if (!empty($flags)) {
                $conditions['flag'] = $flags;
            }

            // Read permissions.
            $res = $this->find('all', array(
                'contain' => array(),
                'conditions' => $conditions,
            ));

            // Format with hierarchy.
            foreach ($res as $perm) {
                $key = array_search($perm['object_id'], $path);
                if (!array_key_exists($key, $perms)) {
                    $perms = array();
                }
                array_push($perms[$key], $perm);
            }
        }

        return $perms;
    }

    /**
     * Load all object's permissions, without inheritance, that apply to the passed User, with the given `flags`.
     *
     * @param int $objectId Object ID.
     * @param array $userData User's data. If empty, retreives all permissions.
     * @param int $flags Flags to search for.
     * @return array Object's permissions.
     * @see Permission::loadPath()
     */
    public function load ($objectId, array $userData = null, $flags = null) {
        $perms = $this->loadPath($objectId, $userData, $flag);
        return $perms[0];
    }

    /**
     * Checks whether an object has permissions with the given `flags`.
     *
     * @param int $objectId Object ID.
     * @param int|array $flags Flags to search for.
     * @return array|bool Object's permissions, or `false` if none are set.
     * @see Permission::loadPath()
     * @deprecated
     */
    public function isPermissionSet ($objectId, $flags) {
        $perms = $this->load($objectId, null, $flags);
        return count($perms) ? $perms : false;
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
                $conditions['flag'] = $this->formatFlag($options['flag']);
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
            $conditions['flag'] = $this->formatFlag($options['flag']);
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
            $p['flag'] = $this->formatFlag($p['flag']);

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
            $p['flag'] = $this->formatFlag($p['flag']);

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
     * Returns the permission resulting from the passed permission set. If no permissions at all are applied, assumes full access.
     *
     * @param array $allPerms All permissions, as a tridimensional array, where first depth level represent inheritance depth level, and second level is an array of permissions (see above).
     * @param array $userData User data array containing key `id` and `groups`, as a list of Groups to be checked against. Group "everyone" with ID `0` is always taken into account.
     * @param mixed $type Permission type. Can be either a string (`'read', 'write', 'backend'`) or one of the constants `Permission::PERM_READ`, `Permission::PERM_WRITE`, `Permission::PERM_BACKEND`.
     * @param int $count Count of actually applied permissions.
     * @return int Resulting permission, as one of the constants `PERM_NO`, `PERM_PARTIAL` or `PERM_FULL`.
     */
    public function checkPermissions (array $allPerms, array $userData = null, $type = self::PERM_READ, &$count = null) {
        $groups = $this->getGroups($userData);
        if (in_array('administrator', $groups)) {
            // Administrators always have full permissions.
            return self::PERM_FULL;
        }

        // Choose permission type.
        switch ($type) {
            case 'backend':
            case self::PERM_BACKEND:
                $type = self::PERM_BACKEND;
                break;
            case 'write':
            case self::PERM_WRITE:
                $type = self::PERM_WRITE;
                break;
            case 'read':
            case self::PERM_READ:
            default:
                $type = self::PERM_READ;
        }

        $count = 0;
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
                $count++;

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
        return $count ? $perm : self::PERM_FULL;
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
    public function checkPermissionByUser (array $perms = array(), array $userData) {
        return (bool) $this->checkPermissions($perms, $userData);
    }

    /**
     * Tells whether Object is backend-private for User.
     *
     * @param int $objectId Object ID.
     * @param array $userData User data array.
     * @return boolean Backend-private.
     * @see Permission::checkPermission()
     * @deprecated
     */
    public function isForbidden ($objectId, array $userData) {
        $perms = $this->loadPath($objectId, $userData);
        return !((bool) $this->checkPermissions($perms, $userData, self::PERM_BACKEND));
    }

    /**
     * Tells whether Object is accessible by User.
     *
     * @param int $objectId Object ID.
     * @param array $userData User data array.
     * @param array $perms Permission array as obtained from `Permission::loadPath()`.
     * @return boolean Accessible.
     * @see Permission::checkPermission()
     * @deprecated
     */
    public function isAccessibleByFrontend ($objectId, array $userData, $perms = array()) {
        if (empty($perms)) {
            $perms = $this->loadPath($objectId, $userData);
        }
        return (bool) $this->checkPermissions($perms, $userData);
    }

    /**
     * Returns access level for Object.
     *
     * @param int $objectId Object ID.
     * @param array $userData User data array.
     * @return string Access level.
     * @see Permission::checkPermission()
     * @deprecated
     */
    public function frontendAccess ($objectId, array $userData = array()) {
        $perms = $this->loadPath($objectId, $userData);
        $levels = array(
            self::PERM_NO => 'denied',
            self::PERM_PARTIAL => 'partial',
            self::PERM_FULL => 'full',
        );

        $count;
        $perm = $this->checkPermissions($perms, $userData, self::PERM_READ, $count);

        return $count ? $levels[$perm] : 'free';
    }
}
