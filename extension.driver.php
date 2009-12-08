<?php

	class extension_youtube_videos extends Extension {

		public function about() {
			return array(
				'name'			=> 'Field: YouTube',
				'version'		=> '0.1',
				'release-date'	=> '2009-12-07',
				'author'		=> array(
					'name'			=> 'Rainer Borene',
					'website'		=> 'http://rainerborene.com',
					'email'			=> 'rainerborene@gmail.com'
				)
			);
		}

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/administration/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => '__appendJavaScript'
				)
			);
		}

		public function __appendJavaScript($context){
			if(isset(Administration::instance()->Page->_context['section_handle']) && in_array(Administration::instance()->Page->_context['page'], array('new', 'edit'))){
				Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/youtube_videos/assets/youtube_video.css', 'screen', 190);
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/youtube_videos/assets/youtube_video.js', 195);
			}
		}

		public function uninstall() {
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_youtube_video`");
		}

		public function install() {
			return $this->_Parent->Database->query("
				CREATE TABLE `tbl_fields_youtube_video` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`refresh` int(11) unsigned NOT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
		}

	}
