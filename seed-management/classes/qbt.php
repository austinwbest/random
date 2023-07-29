<?php

/*
----------------------------------
 ------  Created: 020423   ------
 ------  Austin Best	   ------
----------------------------------
*/

class qbtApi
{
	private $user;
	private $pass;
	private $url;
	private $cookie;

    public function __construct($url, $user, $pass)
    {
		$this->url 		= $url;
		$this->user 	= $user;
		$this->pass 	= $pass;
		$this->cookie 	= $this->login();
    }

    public function __toString()
    {
        return 'qbtApi initialized';
    }

	private function login()
	{
		$cookie 	= '';
		$postData 	= ['username' => $this->user, 'password' => $this->pass];
		
		$url = $this->url . 'api/v2/auth/login';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [$url]);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response 	= curl_exec($ch);
		$error 		= curl_error($ch);
		$info 		= curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code'] != '200') {
			$this->exception(__CLASS__, __FUNCTION__, __LINE__, 'API failure (' . $info['http_code'] . ')', json_decode($response, true));
		}

		$headers = explode("\n", $response);

		foreach ($headers as $header) {
			if (strpos($header, 'set-cookie:') !== false) {
				$headerParts 	= explode(';', $header);
				$cookie 		= reset($headerParts);
				$cookie 		= str_replace('set-cookie: ', '', $cookie);
				break;
			}
		}

		return $cookie;
	}

	public function queue()
	{
		$url = $this->url . 'api/v2/torrents/info?sort=added_on&reverse=true';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:'. $this->cookie));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response 	= curl_exec($ch);
		$error 		= curl_error($ch);
		$info 		= curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code'] != '200') {
			$this->exception(__CLASS__, __FUNCTION__, __LINE__, 'API failure (' . $info['http_code'] . ')', json_decode($response, true));
		}

		$torrents = json_decode($response, true);

		if (!$torrents) {
			$this->exception(__CLASS__, __FUNCTION__, __LINE__, 'No torrents in queue', $torrents);
		}

		return $torrents;
	}

	public function pause($hash)
	{
		$url = $this->url . 'api/v2/torrents/pause?hashes='. $hash;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:' . $this->cookie));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response 	= curl_exec($ch);
		$error 		= curl_error($ch);
		$info 		= curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code'] != '200') {
			$this->exception(__CLASS__, __FUNCTION__, __LINE__, 'API failure (' . $info['http_code'] . ')', json_decode($response, true));
		}
	}

	function remove($hash)
	{
		$url = $this->url . 'api/v2/torrents/delete?hashes=' . $hash . '&deleteFiles=false';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:'. $this->cookie));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response 	= curl_exec($ch);
		$error 		= curl_error($ch);
		$info 		= curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code'] != '200') {
			$this->exception(__CLASS__, __FUNCTION__, __LINE__, 'API failure (' . $info['http_code'] . ')', json_decode($response, true));
		}
	}

	public function tagExists($tag, $tags)
	{
		if (empty($tags)) {
			return false;
		}

		$tags = array_map(function($t) {
			$t = trim($t);
			$t = strtolower($t);

			return $t;
		}, $tags);

		if (in_array(strtolower($tag), $tags)) {
			return true;
		}

		return false;
	}
	
	public function addTagToTorrent($tag, $hash)
	{
		$postData = ['hashes' => $hash, 'tags' => $tag];
		
		$url = $this->url . 'api/v2/torrents/addTags';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:' . $this->cookie));	
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response 	= curl_exec($ch);
		$error 		= curl_error($ch);
		$info 		= curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code'] != '200') {
			$this->exception(__CLASS__, __FUNCTION__, __LINE__, 'API failure (' . $info['http_code'] . ')', json_decode($response, true));
		}
	}
	
	public function removeTagFromTorrent($tag, $hash)
	{
		$postData = ['hashes' => $hash, 'tags' => $tag];
		
		$url = $this->url . 'api/v2/torrents/removeTags';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:' . $this->cookie));	
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response 	= curl_exec($ch);
		$error 		= curl_error($ch);
		$info 		= curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code'] != '200') {
			$this->exception(__CLASS__, __FUNCTION__, __LINE__, 'API failure (' . $info['http_code'] . ')', json_decode($response, true));
		}
	}

	public function getTorrentTrackers($hash)
	{
		$url = $this->url . 'api/v2/torrents/trackers?hash='. $hash;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie:'. $this->cookie));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response 	= curl_exec($ch);
		$error 		= curl_error($ch);
		$info 		= curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code'] != '200' && $info['http_code'] != '404') {
			$this->exception(__CLASS__, __FUNCTION__, __LINE__, 'API failure (' . $info['http_code'] . ')', json_decode($response, true));
		}

		$announcers = json_decode($response, true);

		foreach ($announcers as $index => $announcer) {
			if ($announcer['status'] == 0) {
				unset($announcers[$index]);
			}
		}

		return array_values($announcers);
	}

	private function exception($class, $function, $line, $message, $print = [])
	{
		if ($print) {
			echo 'Reason:<br>';
			echo '<pre>';
			print_r($print);
			echo '</pre>';
		}

		throw new \Exception($class . '->' . $function . ' on line ' . $line . ' failed: ' . $message);
	}
}
