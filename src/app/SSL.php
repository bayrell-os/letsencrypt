<?php

/*!
 * Let’s Encrypt for Cloud OS
 * 
 * MIT License
 * 
 * Copyright (c) 2022 "Ildar Bikmamatov" <support@bayrell.org>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace App;


class SSL
{
	
	/**
	 * Generate ssl group certificate
	 */
	static function generate_ssl_group_certificate($group_id)
	{
		$is_fake = env("GENERATE_FAKE_CERTIFICATE");
		
		$res = \TinyPHP\Bus::call
		(
			"/cloud_os/ssl/get_group/",
			[
				"group_id" => $group_id,
			]
		);
		
		$res->debug();
		
		if (!$res->isSuccess())
		{
			throw new \Exception( $res->error_str );
			return false;
		}
		
		$container_name = $res->result["group"]["container_name"];
		if ($container_name != env("DOCKER_SERVICE_NAME"))
		{
			throw new \Exception("Container is not allowed for this group");
			return false;
		}
		
		$domains = $res->result["domains"];
		if (count($domains) == 0)
		{
			throw new \Exception("Domains is empty");
			return false;
		}
		
		$app = app();
		$app->add_shutdown(function() use ($group_id, $domains, $is_fake)
		{
			/* Generate ssl certificate for current group */
			if ($is_fake == "1")
			{
				static::fake_generate_certificate($group_id, $domains);
			}
			else
			{
				static::letsencrypt_generate_certificate($group_id, $domains);
			}
			
			/* Update all ssl certificates */
			static::update_ssl_certificates();
		});
		
		return true;
	}
	
	
	
	/**
	 * Returns true if file is different
	 */
	static function file_is_different($file1, $file2)
	{
		if (!file_exists($file1)) return true;
		if (!file_exists($file2)) return true;
		
		$file1_content = @file_get_contents($file1);
		$file2_content = @file_get_contents($file2);
		
		return $file1_content != $file2_content;
	}
	
	
	
	/**
	 * Copy file
	 */
	static function copy_file($src, $dest)
	{
		$content = @file_get_contents($src);
		@file_put_contents($dest, $content);
	}
	
	
	
	/**
	 * Returns true if ssl certificate is different
	 */
	static function check_ssl_is_different($group_id)
	{
		$live_path = "/data/letsencrypt/etc/live/grp" . $group_id;
		$save_path = "/data/letsencrypt/etc/live.save/grp" . $group_id;
		
		$live_private_key = $live_path . "/privkey.pem";
		$live_puplic_key = $live_path . "/fullchain.pem";
		
		$save_private_key = $save_path . "/privkey.pem";
		$save_puplic_key = $save_path . "/fullchain.pem";
		
		if (static::file_is_different($live_private_key, $save_private_key)) return true;
		if (static::file_is_different($live_puplic_key, $save_puplic_key)) return true;
		
		return false;
	}
	
	
	
	/**
	 * Save ssl certificate
	 */
	static function save_ssl_certificate($group_id)
	{
		$live_path = "/data/letsencrypt/etc/live/grp" . $group_id;
		$save_path = "/data/letsencrypt/etc/live.save/grp" . $group_id;
		
		$live_private_key = $live_path . "/privkey.pem";
		$live_puplic_key = $live_path . "/fullchain.pem";
		
		$save_private_key = $save_path . "/privkey.pem";
		$save_puplic_key = $save_path . "/fullchain.pem";
		
		if (!file_exists($save_path))
		{
			mkdir($save_path, 0775, true);
		}
		
		static::copy_file($live_private_key, $save_private_key);
		static::copy_file($live_puplic_key, $save_puplic_key);
	}
	
	
	
	/**
	 * Update ssl certificates
	 */
	static function update_ssl_certificates()
	{
		if (!file_exists("/data/letsencrypt/etc/live"))
		{
			return false;
		}
		
		/* Get groups */
		$groups = @scandir("/data/letsencrypt/etc/live");
		
		/* Filter groups */
		$groups = array_filter($groups, function($group_name){
			if (in_array($group_name, [".", ".."])) return false;
			if (substr($group_name, 0, 3) != "grp") return false;
			return true;
		});
		
		/* Update ssl certificates */
		foreach ($groups as $group_name)
		{
			$group_id = substr($group_name, 3);
			
			$is_different = static::check_ssl_is_different($group_id);
			if ($is_different && $group_id)
			{
				echo "Group " . $group_id . "\n";
				
				$live_path = "/data/letsencrypt/etc/live/grp" . $group_id;
				$live_private_key = $live_path . "/privkey.pem";
				$live_puplic_key = $live_path . "/fullchain.pem";
				
				$private_key = @file_get_contents($live_private_key);
				$public_key = @file_get_contents($live_puplic_key);
				
				$res = \TinyPHP\Bus::call
				(
					"/cloud_os/ssl/update_group/",
					[
						"group_id" => $group_id,
						"private_key" => $private_key,
						"public_key" => $public_key,
					]
				);
				
				$res->debug();
				
				if ($res->isSuccess())
				{
					static::save_ssl_certificate($group_id);
				}
				else
				{
					echo "Error: " . $res->error_str . "\n";
				}
			}
		}
		
		return true;
	}
	
	
	
	/**
	 * Fake generate certifiate
	 */
	static function fake_generate_certificate($group_id, $domains)
	{
		$path = "/data/letsencrypt/etc/live/grp" . $group_id;
		$path_res = "/data/letsencrypt/etc/result";
		$path_res_file = $path_res . "/grp" . $group_id . ".txt";
		
		if (!file_exists($path))
		{
			mkdir($path, 0775, true);
		}
		if (!file_exists($path_res))
		{
			mkdir($path_res, 0775, true);
		}
		
		$private_key = $path . "/privkey.pem";
		$puplic_key = $path . "/fullchain.pem";
		
		$cmd = "openssl req -x509 -new -nodes" . 
			" -newkey rsa:4096 -keyout " . $private_key .
			" -out " . $puplic_key . " -sha256 -days 365" .
			" -subj \"/C=EN/ST=TEST/L=TEST/O=TEST/CN=www.example.com\""
		;
		$cmd .= " >" . $path_res_file . " 2>" . $path_res_file;
		echo $cmd;
		
		$date = date("Y-m-d H:i:s", time());
		system($cmd);
		system("echo '' >> " . $path_res_file);
		system("echo '" . $cmd . "' >> " . $path_res_file);
		system("echo '' >> " . $path_res_file);
		system("echo 'End grp" . $group_id . " " . $date . "' >> " . $path_res_file);
	}
	
	
	
	/**
	 * Let's Encrypt generate certifiate
	 */
	static function letsencrypt_generate_certificate($group_id, $domains)
	{
		/* Email */
		$email = env("EMAIL");
		
		/* Domains string */
		$domains = implode(" -d ", $domains);
		
		$path_res = "/data/letsencrypt/etc/result";
		$path_res_file = $path_res . "/grp" . $group_id . ".txt";
		if (!file_exists($path_res))
		{
			mkdir($path_res, 0775, true);
		}
		
		/* Certbot command */
		$cmd = "certbot certonly --non-interactive --agree-tos --email " . $email .
			" --cert-name grp" . $group_id .
			" --webroot --webroot-path=/var/www/letsencrypt" .
			" -d " . $domains;
		$cmd .= " >" . $path_res_file . " 2>" . $path_res_file;
		echo $cmd . "\n";
		
		$date = date("Y-m-d H:i:s", time());
		system($cmd);
		system("echo '' >> " . $path_res_file);
		system("echo '" . $cmd . "' >> " . $path_res_file);
		system("echo '' >> " . $path_res_file);
		system("echo 'End grp" . $group_id . " " . $date . "' >> " . $path_res_file);
	}
}