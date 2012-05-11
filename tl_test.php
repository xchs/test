<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Stefan Lindecke 2012
 * @author     Stefan Lindecke <stefan@chektrion.de>
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @package    GitHub Client
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


/**
 * Table tl_rep_git_client
 */
$GLOBALS['TL_DCA']['tl_rep_git_client'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ctable'                      => array('tl_rep_git_client_projects'),
		'enableVersioning'            => true,
		'switchToEdit'                => true,
		'label'                       => &$GLOBALS['TL_LANG']['MOD']['rep_git_client'][0],
		'onsubmit_callback' => array
		(
			array('tl_rep_git_client', 'receiveBranches')
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('repUser'),
			'flag'                    => 1,
			'panelLayout'             => 'search,sort,filter,limit'
		),
		'label' => array
		(
			'fields'                  => array('repUser', 'repRepository'),
			'format'                  => '%s :: %s'
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_rep_git_client']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'edit_installs' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_rep_git_client']['edit_installs'],
				'href'                => 'table=tl_rep_git_client_projects',
				'icon'                => 'header.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_rep_git_client']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_rep_git_client']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},repUser,repRepository'
	),

	// Fields
	'fields' => array
	(
		'repUser' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_rep_git_client']['repUser'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255),
			'save_callback' => array
			(
				array('tl_rep_git_client', 'importUserRepos')
			)
		),
		'repRepository' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_rep_git_client']['repRepository'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_rep_git_client', 'getUserRepos'),
			'eval'                    => array('mandatory'=>true)
		)
	)
);


/**
 * Class tl_rep_git_client
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Stefan Lindecke 2012
 * @author     Stefan Lindecke <stefan@chektrion.de>
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @package    GitHub Client
 */
class tl_rep_git_client extends Backend
{

	/**
	 * Import the GitHub API library and the Database object
	 */
	public function __construct()
	{
		include (TL_ROOT . "/plugins/github/ApiInterface.php");
		include (TL_ROOT . "/plugins/github/Api.php");
		include (TL_ROOT . "/plugins/github/Api/Repo.php");
		include (TL_ROOT . "/plugins/github/Api/Object.php");
		include (TL_ROOT . "/plugins/github/Autoloader.php");
		include (TL_ROOT . "/plugins/github/Client.php");
		include (TL_ROOT . "/plugins/github/HttpClientInterface.php");
		include (TL_ROOT . "/plugins/github/HttpClient.php");
		include (TL_ROOT . "/plugins/github/HttpClient/Curl.php");
		include (TL_ROOT . "/plugins/github/HttpClient/Exception.php");


		// Do not use this autoloader. Will not work with Contao autoloader
		// Github_Autoloader::register();
		$this->objGithub = new Github_Client();
		$this->import("Database");
	}


	/**
	 * Import the user repositories
	 * @param  mixed         $varValue The property value
	 * @param  DataContainer $dc       The DataContainer object
	 * @return mixed                   The result value
	 */
	public function importUserRepos($varValue, DataContainer $dc)
	{
		return $varValue;
	}


	/**
	 * Get the user repositories
	 * @param  DataContainer $dc The DataContainer object
	 * @return Array             An array with the user repositories
	 */
	public function getUserRepos(DataContainer $dc)
	{
		$arrValues = array();

		if ($dc->activeRecord->repUser)
		{

			try
			{
				$arrRepos = $this->objGithub->getRepoApi()->getUserRepos($dc->activeRecord->repUser);

				foreach ($arrRepos as $repo)
				{
					$arrValues[$repo['name']] = $repo['name'];

				}
			}

			catch (Exception $e)
			{
			}

		}

		return $arrValues;
	}


	/**
	 * Get the repository branches
	 * @param  DataContainer $dc The DataContainer object
	 */
	public function receiveBranches(DataContainer $dc)
	{
		if ( $dc->activeRecord->repRepository)
		{
			$arrTags     = $this->objGithub->getRepoApi()->getRepoTags($dc->activeRecord->repUser, $dc->activeRecord->repRepository);
			$arrBranches = $this->objGithub->getRepoApi()->getRepoBranches($dc->activeRecord->repUser, $dc->activeRecord->repRepository);
			$arrRepos    = $this->objGithub->getRepoApi()->getUserRepos($dc->activeRecord->repUser);

			$arrMyRepo = array();
			foreach ($arrRepos as $repo)
			{
				if ($repo['name']==$dc->activeRecord->repRepository)
				{
					$arrMyRepo = $repo;
				}
			}

			if ((is_array($arrBranches)) && (count($arrBranches)>0))
			{
				foreach ($arrBranches as $key=>$value)
				{
					$tree  = $this->objGithub->getObjectApi()->showTree($dc->activeRecord->repUser, $dc->activeRecord->repRepository, $value);
					$blobs = $this->objGithub->getObjectApi()->listBlobs($dc->activeRecord->repUser, $dc->activeRecord->repRepository, $value);


					$objBranch = new libContaoConnector("tl_rep_git_client_projects","repHash",$value);
					$objBranch->pid=$dc->id;
					$objBranch->repUrl = $arrMyRepo['url'];
					$objBranch->repPushed = $arrMyRepo['pushed_at'];
					$objBranch->repBranch = $key;
					$objBranch->repHash = $value;
					$objBranch->allFiles = $blobs;
					$objBranch->ignoredFiles = array();
					$objBranch->repType = 'BRANCH';

					$objBranch->Sync();
				}
			}

			if ((is_array($arrTags)) && (count($arrTags)>0))
			{
				foreach ($arrTags as $key=>$value)
				{
					$tree  = $this->objGithub->getObjectApi()->showTree($dc->activeRecord->repUser, $dc->activeRecord->repRepository, $value);
					$blobs = $this->objGithub->getObjectApi()->listBlobs($dc->activeRecord->repUser, $dc->activeRecord->repRepository, $value);


					$objBranch = new libContaoConnector("tl_rep_git_client_projects","repHash",$value);
					$objBranch->pid=$dc->id;
					$objBranch->repUrl = $arrMyRepo['url'];
					$objBranch->repPushed = $arrMyRepo['pushed_at'];
					$objBranch->repBranch = $key;
					$objBranch->repHash = $value;
					$objBranch->allFiles = $blobs;
					$objBranch->ignoredFiles = array();
					$objBranch->repType = 'TAGS';

					$objBranch->Sync();
				}
			}
		}
	}
}
