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
	 * Returns letsencrypt path
	 */
	static function get_letsencrypt_path($group_name)
	{
		return "/data/letsencrypt/live/" . $group_name;
	}
	
	
	
	/**
     * Returns letsencrypt save path
     */
    static function get_letsencrypt_save_path()
    {
        return "/data/letsencrypt/save/" . $group_name;
    }
	
	
	
	/**
	 * Generate ssl group certificate
	 */
	static function generate_ssl_group_certificate($group_id)
	{
		$email = env("EMAIL");
		$is_fake = env("GENERATE_FAKE_CERTIFICATE");
		$group_name = "grp" . $group_id;
		
		$res = \TinyPHP\Bus::call
		(
			"/cloud_os/ssl/get_group/",
			[
				"group_id" => $group_id,
			]
		);
		
		// $res->debug();
		
		if (!$res->isSuccess())
		{
			throw new \Exception( $res->error_str );
			return false;
		}
		
		$domains = $res->result["domains"];
		if (count($domains) == 0)
		{
			return false;
		}
		
		/* Generate ssl certificate for current group */
		if ($is_fake == "1")
		{
			static::fake_generate_certificate($group_name, $domains);
		}
		else
		{
			/* Domains string */
			$domains = implode(" -d ", $domains);
			
			/* Certbot command */
			$cmd = "certbot certonly --non-interactive --agree-tos --email " . $email .
				" --cert-name " . $group_name .
				" --webroot --webroot-path=/var/www/letsencrypt" .
				" -d " . $domains;
			
			echo $cmd . "\n";
			system($cmd);
		}
		
		/* Update all ssl certificates */
		static::update_ssl_certificates();
		
		return true;
	}
	
	
	
	/**
	 * Update ssl certificates
	 */
	static function update_ssl_certificates()
	{
		
	}
	
	
	
	/**
	 * Fake generate certifiate
	 */
	static function fake_generate_certificate($group_name, $domains)
	{
		$path = static::get_letsencrypt_path($group_name);
		
		if (!file_exists($path))
		{
			mkdir($path, 0775, true);
		}
		
		$private_key = $path . "/privkey.pem";
		$puplic_key = $path . "/fullchain.pem";
		
		$cmd = "openssl req -x509 -new -nodes" . 
			" -newkey rsa:4096 -keyout " . $private_key .
			" -out " . $puplic_key . " -sha256 -days 365" .
			" -subj \"/C=EN/ST=TEST/L=TEST/O=TEST/CN=www.example.com\""
		;
		
		echo $cmd . "\n";
		system($cmd);
	}
	
}