<?php
class PatchModule extends CWebModule
{
	private $assetsUrl = null;
	
	public function init()
	{
		// this method is called when the module is being created
		// you may place code here to customize the module or the application
	
//		$this->version = '0.7.0';
		
		// import the module-level models and components
		$this->setImport(array(
				'patch.models.*',
				'patch.components.*',
		));
	}
	
	public function getAssetsUrl()
	{
		if (is_null($this->assetsUrl)) {
			$this->assetsUrl = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('patch.assets'));
		}
		return $this->assetsUrl;
	}

	public static function t($category, $message, $params=array(), $language=NULL) {
		return Yii::t($category, $message, $params, 'patchMessages', $language);
	}
}