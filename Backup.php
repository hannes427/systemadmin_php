<?php
namespace FreePBX\modules\Systemadmin;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
	public function runBackup($id,$transaction){
		$configs = [
			'tables' => $this->dumpTables(true),
			'settings' => $this->dumpAdvancedSettings()
		];

		$this->addConfigs($configs);
	}
}
