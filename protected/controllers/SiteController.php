<?php
/*
 * Copyright (C) 2012 FOSS-Group
 *                    Germany
 *                    http://www.foss-group.de
 *                    support@foss-group.de
 *
 * Authors:
 *  Christian Wittkowski <wittkowski@devroom.de>
 *
 * Licensed under the EUPL, Version 1.1 or – as soon they
 * will be approved by the European Commission - subsequent
 * versions of the EUPL (the "Licence");
 * You may not use this work except in compliance with the
 * Licence.
 * You may obtain a copy of the Licence at:
 *
 * http://www.osor.eu/eupl
 *
 * Unless required by applicable law or agreed to in
 * writing, software distributed under the Licence is
 * distributed on an "AS IS" basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied.
 * See the Licence for the specific language governing
 * permissions and limitations under the Licence.
 *
 *
 */

/**
 * SiteController class file.
 *
 * @author: Christian Wittkowski <wittkowski@devroom.de>
 * @version: 0.4
 */

class SiteController extends Controller
{
	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
			'captcha'=>array(
				'class'=>'CCaptchaAction',
				'backColor'=>0xFFFFFF,
			),
			// page action renders "static" pages stored under 'protected/views/site/pages'
			// They can be accessed via: index.php?r=site/page&view=FileName
			'page'=>array(
				'class'=>'CViewAction',
			),
		);
	}

		/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex()
	{
		$this->render('index');
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
	    if($error=Yii::app()->errorHandler->error)
	    {
	    	if(Yii::app()->request->isAjaxRequest)
	    		echo $error['message'];
	    	else
	        	$this->render('error', $error);
	    }
	}

	/**
	 * Displays the contact page
	 */
	public function actionContact()
	{
		$model=new ContactForm;
		if(isset($_POST['ContactForm']))
		{
			$model->attributes=$_POST['ContactForm'];
			if($model->validate())
			{
				$headers="From: {$model->email}\r\nReply-To: {$model->email}";
				mail(Yii::app()->params['adminEmail'],$model->subject,$model->body,$headers);
				Yii::app()->user->setFlash('contact','Thank you for contacting us. We will respond to you as soon as possible.');
				$this->refresh();
			}
		}
		$this->render('contact',array('model'=>$model));
	}

	/**
	 * Displays the login page
	 */
	public function actionLogin()
	{
		if (!Yii::app()->user->isGuest) {
			if (Yii::app()->user->isAdmin) {
				$this->redirect('site');
			}
			else {
				$this->redirect('vmList/index');
			}
		}

		$model = new LoginForm();

		// if it is ajax validation request
		if(isset($_POST['ajax']) && $_POST['ajax'] === 'login-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		// collect user input data
		if(isset($_POST['LoginForm']))
		{
			$model->attributes = $_POST['LoginForm'];
			// validate user input and redirect
			if($model->validate() && $model->login())
			{
				if (Yii::app()->user->isAdmin) {
					$this->redirect('site');
				}
				else {
					$this->redirect('vmList/index');
				}
			}
		}

		$realms = array();
		$server = CLdapServer::getInstance();
		$result = $server->search('ou=authentication,ou=virtualization,ou=services', '(&(objectClass=sstLDAPAuthenticationProvider))', array('ou'));
		//echo '<pre>' . print_r($result, true) . '</pre>';
		for($i=0; $i<$result['count']; $i++) {
			$realms[$result[$i]['ou'][0]] = $result[$i]['ou'][0];
		}
		// display the login form
		$this->render('login',array('model'=>$model, 'realms' => $realms));
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}

	/**
	 * Change the display language
	 */
	public function actionChangeLanguage()
	{
		if (isset($_POST['lang'])) {
			$lang = $_POST['lang'];
			if (!file_exists(Yii::app()->getBasePath() . '/messages/' . $lang)) {
				$lang = 'en';
			}
			Yii::app()->user->setState('lang', $lang);
// 			Yii::app()->user->renewCookie();
			$this->redirect(Yii::app()->getRequest()->getUrlReferrer());
		}
	}
}