<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

namespace OC\Core\Command\Integrity;

use OC\IntegrityCheck\Checker;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SignApp
 *
 * @package OC\Core\Command\Integrity
 */
class SignApp extends Command {
	/** @var Checker */
	private $checker;
	/** @var FileAccessHelper */
	private $fileAccessHelper;

	/**
	 * @param Checker $checker
	 * @param FileAccessHelper $fileAccessHelper
	 */
	public function __construct(Checker $checker,
								FileAccessHelper $fileAccessHelper) {
		parent::__construct(null);
		$this->checker = $checker;
		$this->fileAccessHelper = $fileAccessHelper;
	}

	protected function configure() {
		$this
			->setName('integrity:sign-app')
			->setDescription('Sign app using a private key.')
			->addOption('appId', null, InputOption::VALUE_REQUIRED, 'Application to sign')
			->addOption('privateKey', null, InputOption::VALUE_REQUIRED, 'Path to private key to use for signing')
			->addOption('certificate', null, InputOption::VALUE_REQUIRED, 'Path to certificate to use for signing');
	}

	/**
	 * {@inheritdoc }
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$appId = $input->getOption('appId');
		$privateKeyPath = $input->getOption('privateKey');
		$keyBundlePath = $input->getOption('certificate');
		if(is_null($appId) || is_null($privateKeyPath) || is_null($keyBundlePath)) {
			$output->writeln('--appId, --privateKey and --certificate are required.');
			return null;
		}

		$privateKey = $this->fileAccessHelper->file_get_contents($privateKeyPath);
		$keyBundle = $this->fileAccessHelper->file_get_contents($keyBundlePath);

		if($privateKey === false) {
			$output->writeln(sprintf('Private key "%s" does not exists.', $privateKeyPath));
			return null;
		}

		if($keyBundle === false) {
			$output->writeln(sprintf('Certificate "%s" does not exists.', $keyBundlePath));
			return null;
		}

		$rsa = new RSA();
		$rsa->loadKey($privateKey);
		$x509 = new X509();
		$x509->loadX509($keyBundle);
		$x509->setPrivateKey($rsa);
		$this->checker->writeAppSignature($appId, $x509, $rsa);

		$output->writeln('Successfully signed "'.$appId.'"');
	}
}
