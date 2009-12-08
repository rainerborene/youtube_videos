<?php

	Class YouTubeHelper {

		public static function getVideoId($data){
			$url = parse_url($data);

			if (is_array($url) && preg_match('/youtube\.com/i', $url['host'])){
				if (preg_match('/v=(?<id>[a-z0-9-_]+)/i', $url['query'], $match)){
					return $match['id'];
				}
			}
	 	}

		public static function getVideoFeed($video_id){
			return DOMDocument::load("http://gdata.youtube.com/feeds/api/videos/{$video_id}");
		}

		public static function getVideoInfo($video_id){
			// namespaces
			$ns = array();
			$ns['media'] = 'http://search.yahoo.com/mrss/';
			$ns['yt'] = 'http://gdata.youtube.com/schemas/2007';

			// response xml
			$video = YouTubeHelper::getVideoFeed($video_id);

			if (!$video) return;

			$entry = $video->getElementsByTagName('entry')->item(0);
			$media = $entry->getElementsByTagNameNS($ns['media'], 'group')->item(0);
			$author = $entry->getElementsByTagName('author')->item(0);
			$statistics = $entry->getElementsByTagNameNS($ns['yt'], 'statistics')->item(0);

			$data = array(
				'video_id' => $video_id,
				'title' => $entry->getElementsByTagName('title')->item(0)->nodeValue,
				'description' => $entry->getElementsByTagName('content')->item(0)->nodeValue,
				'keywords' => $media->getElementsByTagNameNS($ns['media'], 'keywords')->item(0)->nodeValue,
				'duration' => $media->getElementsByTagNameNS($ns['yt'], 'duration')->item(0)->getAttribute('seconds'),
				'favorites' => $statistics->getAttribute('favoriteCount'),
				'views' => $statistics->getAttribute('viewCount'),
				'user_name' => $author->getElementsByTagName('name')->item(0)->nodeValue,
				'user_url' => 'http://www.youtube.com/user/' . strtolower($author->getElementsByTagName('name')->item(0)->nodeValue),
				'published_date' => @strtotime($entry->getElementsByTagName('published')->item(0)->nodeValue),
				'last_updated' => time()
			);

			return $data;
		}

		public static function updateVideoInfo($video_id, $field_id, $entry_id, $database){
			$data = YouTubeHelper::getVideoInfo($clip_id);
			if (!$data) return;

			$database->update($data, "sym_entries_data_{$field_id}", "entry_id={$entry_id}");
			return $data;
		}

	}
