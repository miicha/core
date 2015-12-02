<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing\Tests\API;

use OC\Share20\IShare;
use OCA\Files_Sharing\API\Share20OCS;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Files\IRootFolder;

class Share20OCSTest extends \Test\TestCase {

	/** @var \OC\Share20\Manager */
	private $shareManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IRequest */
	private $request;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IUser */
	private $currentUser;

	/** @var Share20OCS */
	private $ocs;

	protected function setUp() {
		$this->shareManager = $this->getMockBuilder('OC\Share20\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager = $this->getMock('OCP\IGroupManager');
		$this->userManager = $this->getMock('OCP\IUserManager');
		$this->request = $this->getMock('OCP\IRequest');
		$this->rootFolder = $this->getMock('OCP\Files\IRootFolder');
		$this->urlGenerator = $this->getMock('OCP\IURLGenerator');
		$this->currentUser = $this->getMock('OCP\IUser');

		$this->ocs = new Share20OCS(
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->request,
				$this->rootFolder,
				$this->urlGenerator,
				$this->currentUser
		);
	}

	public function testDeleteShareShareNotFound() {
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->will($this->throwException(new \OC\Share20\Exception\ShareNotFound()));

		$expected = new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		$this->assertEquals($expected, $this->ocs->deleteShare(42));
	}

	public function testDeleteShareCouldNotDelete() {
		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareOwner')->willReturn($this->currentUser);
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->willReturn($share);
		$this->shareManager
			->expects($this->once())
			->method('deleteShare')
			->with($share)
			->will($this->throwException(new \OC\Share20\Exception\BackendError()));


		$expected = new \OC_OCS_Result(null, 404, 'could not delete share');
		$this->assertEquals($expected, $this->ocs->deleteShare(42));
	}

	public function testDeleteShare() {
		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getSharedBy')->willReturn($this->currentUser);
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->willReturn($share);
		$this->shareManager
			->expects($this->once())
			->method('deleteShare')
			->with($share);

		$expected = new \OC_OCS_Result();
		$this->assertEquals($expected, $this->ocs->deleteShare(42));
	}

	public function testGetGetShareNotExists() {
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->will($this->throwException(new \OC\Share20\Exception\ShareNotFound()));

		$expected = new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		$this->assertEquals($expected, $this->ocs->getShare(42));
	}

	public function createShare($id, $shareType, $sharedWith, $sharedBy, $shareOwner, $path, $permissions,
								$shareTime, $expiration, $parent, $target, $mail_send, $token=null,
								$password=null) {
		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getId')->willReturn($id);
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getSharedBy')->willReturn($sharedBy);
		$share->method('getShareOwner')->willReturn($shareOwner);
		$share->method('getPath')->willReturn($path);
		$share->method('getPermissions')->willReturn($permissions);
		$share->method('getShareTime')->willReturn($shareTime);
		$share->method('getExpirationDate')->willReturn($expiration);
		$share->method('getParent')->willReturn($parent);
		$share->method('getTarget')->willReturn($target);
		$share->method('getMailSend')->willReturn($mail_send);
		$share->method('getToken')->willReturn($token);
		$share->method('getPassword')->willReturn($password);

		return $share;
	}

	public function dataGetShare() {
		$data = [];

		$owner = $this->getMock('OCP\IUser');
		$owner->method('getUID')->willReturn('ownerId');
		$owner->method('getDisplayName')->willReturn('ownerDisplay');

		$user = $this->getMock('OCP\IUser');
		$user->method('getUID')->willReturn('userId');
		$user->method('getDisplayName')->willReturn('userDisplay');

		$group = $this->getMock('OCP\IGroup');
		$group->method('getGID')->willReturn('groupId');

		$storage = $this->getMock('OCP\Files\Storage');
		$storage->method('getId')->willReturn('STORAGE');

		$parentFolder = $this->getMock('OCP\Files\Folder');
		$parentFolder->method('getId')->willReturn(3);

		$file = $this->getMock('OCP\Files\File');
		$file->method('getId')->willReturn(1);
		$file->method('getPath')->willReturn('file');
		$file->method('getStorage')->willReturn($storage);
		$file->method('getParent')->willReturn($parentFolder);

		$folder = $this->getMock('OCP\Files\Folder');
		$folder->method('getId')->willReturn(2);
		$folder->method('getPath')->willReturn('folder');
		$folder->method('getStorage')->willReturn($storage);
		$folder->method('getParent')->willReturn($parentFolder);

		// File shared with user
		$share = $this->createShare(
			100,
			\OCP\Share::SHARE_TYPE_USER,
			$user,
			$owner,
			$owner,
			$file,
			4,
			5,
			null,
			6,
			'target',
			0
		);
		$expected = [
			'id' => 100,
			'share_type' => \OCP\Share::SHARE_TYPE_USER,
			'share_with' => 'userId',
			'share_with_displayname' => 'userDisplay',
			'uid_owner' => 'ownerId',
			'displayname_owner' => 'ownerDisplay',
			'item_type' => 'file',
			'item_source' => 1,
			'file_source' => 1,
			'file_target' => 'target',
			'file_parent' => 3,
			'token' => null,
			'expiration' => null,
			'permissions' => 4,
			'stime' => 5,
			'parent' => 6,
			'storage_id' => 'STORAGE',
			'path' => 'file',
			'storage' => null, // HACK around static function
			'mail_send' => 0,
		];
		$data[] = [$share, $expected];

		// Folder shared with group
		$share = $this->createShare(
			101,
			\OCP\Share::SHARE_TYPE_GROUP,
			$group,
			$owner,
			$owner,
			$folder,
			4,
			5,
			null,
			6,
			'target',
			0
		);
		$expected = [
			'id' => 101,
			'share_type' => \OCP\Share::SHARE_TYPE_GROUP,
			'share_with' => 'groupId',
			'share_with_displayname' => 'groupId',
			'uid_owner' => 'ownerId',
			'displayname_owner' => 'ownerDisplay',
			'item_type' => 'folder',
			'item_source' => 2,
			'file_source' => 2,
			'file_target' => 'target',
			'file_parent' => 3,
			'token' => null,
			'expiration' => null,
			'permissions' => 4,
			'stime' => 5,
			'parent' => 6,
			'storage_id' => 'STORAGE',
			'path' => 'folder',
			'storage' => null, // HACK around static function
			'mail_send' => 0,
		];
		$data[] = [$share, $expected];

		// File shared by link with Expire
		$expire = \DateTime::createFromFormat('Y-m-d h:i:s', '2000-01-02 01:02:03');
		$share = $this->createShare(
			101,
			\OCP\Share::SHARE_TYPE_LINK,
			null,
			$owner,
			$owner,
			$folder,
			4,
			5,
			$expire,
			6,
			'target',
			0,
			'token',
			'password'
		);
		$expected = [
			'id' => 101,
			'share_type' => \OCP\Share::SHARE_TYPE_LINK,
			'share_with' => 'password',
			'share_with_displayname' => 'password',
			'uid_owner' => 'ownerId',
			'displayname_owner' => 'ownerDisplay',
			'item_type' => 'folder',
			'item_source' => 2,
			'file_source' => 2,
			'file_target' => 'target',
			'file_parent' => 3,
			'token' => 'token',
			'expiration' => '2000-01-02 00:00:00',
			'permissions' => 4,
			'stime' => 5,
			'parent' => 6,
			'storage_id' => 'STORAGE',
			'path' => 'folder',
			'storage' => null, // HACK around static function
			'mail_send' => 0,
			'url' => 'url',
		];
		$data[] = [$share, $expected];

		return $data;
	}

	/**
	 * @dataProvider dataGetShare
	 */
	public function testGetShare(\OC\Share20\IShare $share, array $result) {
		$ocs = $this->getMockBuilder('OCA\Files_Sharing\API\Share20OCS')
				->setConstructorArgs([
					$this->shareManager,
					$this->groupManager,
					$this->userManager,
					$this->request,
					$this->rootFolder,
					$this->urlGenerator,
					$this->currentUser
				])->setMethods(['canAccessShare'])
				->getMock();

		$ocs->method('canAccessShare')->willReturn(true);

		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with($share->getId())
			->willReturn($share);

		$userFolder = $this->getMock('OCP\Files\Folder');
		$userFolder
			->method('getRelativePath')
			->will($this->returnArgument(0));

		$this->rootFolder->method('getUserFolder')
			->with($share->getShareOwner()->getUID())
			->willReturn($userFolder);

		$this->urlGenerator
			->method('linkToRouteAbsolute')
			->willReturn('url');

		$expected = new \OC_OCS_Result($result);
		$this->assertEquals($expected->getData(), $ocs->getShare($share->getId())->getData());
	}

	public function testCanAccessShare() {
		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareOwner')->willReturn($this->currentUser);
		$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getSharedBy')->willReturn($this->currentUser);
		$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_USER);
		$share->method('getSharedWith')->willReturn($this->currentUser);
		$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_USER);
		$share->method('getSharedWith')->willReturn($this->getMock('OCP\IUser'));
		$this->assertFalse($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_GROUP);
		$group = $this->getMock('OCP\IGroup');
		$group->method('inGroup')->with($this->currentUser)->willReturn(true);
		$share->method('getSharedWith')->willReturn($group);
		$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_GROUP);
		$group = $this->getMock('OCP\IGroup');
		$group->method('inGroup')->with($this->currentUser)->willReturn(false);
		$share->method('getSharedWith')->willReturn($group);
		$this->assertFalse($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_LINK);
		$this->assertFalse($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));
	}
}
