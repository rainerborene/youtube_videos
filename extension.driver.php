<?php	
	class extension_youtube_videos extends Extension {

		public function about() {
			return array(
				'name'			=> 'Field: YouTube',
				'version'		=> '0.3',
				'release-date'	=> '2010-06-01',
				'author'		=> array(
					'name'			=> 'Rainer Borene',
					'website'		=> 'http://rainerborene.com',
					'email'			=> 'me@rainerborene.com'
				)
			);
		}

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/administration/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => '__appendResources'
				)
			);
		}

		public function __appendResources($context){
			$page = Administration::instance()->Page;

			if(isset($page->_context['section_handle']) && in_array($page->_context['page'], array('new', 'edit'))){
				$page->addStylesheetToHead(URL . '/extensions/youtube_videos/assets/youtube_video.css', 'screen', 190);
				$page->addScriptToHead(URL . '/extensions/youtube_videos/assets/youtube_video.js', 195);
			}
		}

		public function uninstall() {
			Symhony::Database()->query("DROP TABLE `tbl_fields_youtube_video`");
		}

		public function install() {
			return Symphony::Database()->query("
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
