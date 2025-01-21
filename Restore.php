<?php
namespace FreePBX\modules\Systemadmin;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		$this->importTables($configs['tables']);
		$dbh = \FreePBX::Database();
		$sql = "TRUNCATE TABLE systemadmin_packetcapture";
		$stmt = $dbh->prepare($sql);
		$stmt->execute();
		if(!empty($configs['settings'])) {
			$this->importAdvancedSettings($configs['settings']);
		}
		$files = $this->getFiles();
		foreach ($files as $file) {
			$filename = $file->getPathTo().'/'.$file->getFilename();
			$source = $this->tmpdir.'/files'.$file->getPathTo().'/'.$file->getFilename();
			$dest = $filename;
			$test = $file->getPathTo();
			if(file_exists($source)){
				@mkdir($dest,0755,true);
				@copy($source, $dest);
			}
		}
	}
}
