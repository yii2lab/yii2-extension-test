<?php

namespace yii2lab\test\helpers;

use ArrayAccess;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\ArraySubset;
use PHPUnit\Util\InvalidArgumentHelper;
use yii2lab\app\domain\helpers\Config;
use yii2lab\app\domain\helpers\Env;
use yii2lab\extension\yii\helpers\FileHelper;

class TestHelper {
	

	public static function copySqlite($dir) {
		
		$sourceFile = $dir . '/db/test.db';
		$targetFile = ROOT_DIR . '/common/runtime/sqlite/test-package.db';
		if(!FileHelper::has($sourceFile)) {
			return;
		}
		FileHelper::copy($sourceFile, $targetFile);
	}
	
	public static function loadEnvFromPath($path) {
		$config = require(ROOT_DIR . DS . TEST_APPLICATION_DIR . DS . 'common/config/env.php');
		$config['app'] = self::replacePath($config['app'], $path);
		$config['config'] = self::replacePath($config['config'], $path);
		return $config;
	}
	
	public static function loadConfigFromPath($path) {
		$definition = Env::get('config');
		$definition = self::replacePath($definition, $path);
		$testConfig = Config::loadData($definition);
		return $testConfig;
	}
	
	public static function loadConfig($name, $dir = TEST_APPLICATION_DIR) {
		$dir = FileHelper::trimRootPath($dir);
		$path = rtrim(ROOT_DIR . DS . $dir, DS);
		$baseConfig = @include($path . DS . $name);
		return $baseConfig;
	}
	
	private static function replacePath($definition, $path) {
		$path = FileHelper::normalizePath($path);
		$path = self::trimPath($path);
		$filters = [];
		foreach(['filters', 'commands'] as $type) {
			if(!empty($definition[$type])) {
				foreach($definition[$type] as $filter) {
					$filter = self::filterItem($filter, $path);
					if(!empty($filter['filters'])) {
                        $filter = self::replacePath($filter, $path);
                    }
					if($filter) {
						$filters[] = $filter;
					}
				}
				$definition[$type] = $filters;
			}
		}
		return $definition;
	}
	private static function filterItem($filter, $path) {
		if(is_string($filter)) {
			return $filter;
		}
		if(!array_key_exists('app', $filter)) {
			return $filter;
		}
		if($filter['app'] == TEST_APPLICATION_DIR . DS . 'console') {
			return null;
		}
		if($filter['app'] == TEST_APPLICATION_DIR . DS . 'common') {
			$filter['app'] = $path;
		}
		return $filter;
	}

	private static function trimPath($path) {
		$path = FileHelper::trimRootPath($path);
		$commonDir = DS . 'config';
		if(strpos($path, $commonDir) !== false) {
			$path = substr($path, 0, - strlen($commonDir));
		}
		return $path;
	}

	public static function assertArraySubset($subset, $array, bool $checkForObjectIdentity = false, string $message = ''): void
	{
		if (!(\is_array($subset) || $subset instanceof ArrayAccess)) {
			throw InvalidArgumentHelper::factory(
				1,
				'array or ArrayAccess'
			);
		}

		if (!(\is_array($array) || $array instanceof ArrayAccess)) {
			throw InvalidArgumentHelper::factory(
				2,
				'array or ArrayAccess'
			);
		}

		$constraint = new ArraySubset($subset, $checkForObjectIdentity);

		Assert::assertThat($array, $constraint, $message);
	}


}
