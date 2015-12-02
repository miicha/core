<?php

/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OC\Avatar;
use OCP\Files\Folder;

class AvatarTest extends \Test\TestCase {
	/** @var  Folder */
	private $folder;

	/** @var  \OC\Avatar */
	private $avatar;

	public function setUp() {
		parent::setUp();

		$this->folder = $this->getMock('\OCP\Files\Folder');
		$l = $this->getMock('\OCP\IL10N');
		$l->method('t')->will($this->returnArgument(0));
		$this->avatar = new \OC\Avatar($this->folder, $l);

	}

	public function testGetNoAvatar() {
		$this->assertEquals(false, $this->avatar->get());
	}

	public function testGetAvatarSizeMatch() {
		$this->folder->method('nodeExists')
			->will($this->returnValueMap([
				['avatar.jpg', true],
				['avatar.128.jpg', true],
			]));

		$expected = new OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');

		$file = $this->getMock('\OCP\Files\File');
		$file->method('getContent')->willReturn($expected->data());
		$this->folder->method('get')->with('avatar.128.jpg')->willReturn($file);

		$this->assertEquals($expected->data(), $this->avatar->get(128)->data());
	}

	public function testGetAvatarNoSizeMatch() {
		$this->folder->method('nodeExists')
			->will($this->returnValueMap([
				['avatar.png', true],
				['avatar.32.png', false],
			]));

		$expected = new OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$expected2 = new OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$expected2->resize(32);

		$file = $this->getMock('\OCP\Files\File');
		$file->method('getContent')->willReturn($expected->data());
		$this->folder->method('get')->with('avatar.png')->willReturn($file);

		$newFile = $this->getMock('\OCP\Files\File');
		$newFile->expects($this->once())
			->method('putContent')
			->with($expected2->data());
		$this->folder->expects($this->once())
			->method('newFile')
			->with('avatar.32.png')
			->willReturn($newFile);

		$this->assertEquals($expected2->data(), $this->avatar->get(32)->data());
	}

	public function testExistsNo() {
		$this->assertFalse($this->avatar->exists());
	}

	public function testExiststJPG() {
		$this->folder->method('nodeExists')
			->will($this->returnValueMap([
				['avatar.jpg', true],
				['avatar.png', false],
			]));
		$this->assertTrue($this->avatar->exists());
	}

	public function testExistsPNG() {
		$this->folder->method('nodeExists')
			->will($this->returnValueMap([
				['avatar.jpg', false],
				['avatar.png', true],
			]));
		$this->assertTrue($this->avatar->exists());
	}

	public function testSetAvatar() {
		$oldFile = $this->getMock('\OCP\Files\File');
		$this->folder->method('get')
			->will($this->returnValueMap([
				['avatar.jpg', $oldFile],
				['avatar.png', $oldFile],
			]));
		$oldFile->expects($this->exactly(2))->method('delete');

		$newFile = $this->getMock('\OCP\Files\File');
		$this->folder->expects($this->once())
			->method('newFile')
			->with('avatar.png')
			->willReturn($newFile);

		$image = new OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$newFile->expects($this->once())
			->method('putContent')
			->with($image->data());

		$this->avatar->set($image->data());
	}

}
