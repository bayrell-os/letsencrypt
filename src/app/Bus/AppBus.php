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

namespace App\Bus;

use TinyPHP\BusApiRoute;
use TinyPHP\RenderContainer;
use TinyPHP\RouteList;
use TinyPHP\Utils;
use App\JWT;
use App\Models\NginxFile;
use App\Models\User;
use App\Models\UserAuth;


class AppBus extends BusApiRoute
{
	
	/**
	 * Declare routes
	 */
	function routes(RouteList $routes)
	{
		$routes->addRoute([
			"methods" => [ "GET", "POST" ],
			"url" => "/api/bus/ssl/generate/",
			"name" => "bus:ssl:generate",
			"method" => [$this, "actionGenerate"],
		]);
		$routes->addRoute([
			"methods" => [ "GET", "POST" ],
			"url" => "/api/bus/ssl/refresh/",
			"name" => "bus:ssl:refresh",
			"method" => [$this, "actionRefresh"],
		]);
	}
	
	
	
	/**
	 * Returns generate ssl certificate
	 */
	function actionGenerate()
	{
		$result = [];
		
		$data = $this->container->post("data");
		$group_id = (int)(isset($data["group_id"]) ? $data["group_id"] : 0);
		
		\App\SSL::generate_ssl_group_certificate($group_id);
		
		$result["group_id"] = $group_id;
		$this->api_result->success
		(
			$result,
			"Run generate SSL for grp" . $group_id . ". Please refresh"
		);
	}
	
	
	
	/**
	 * Returns result generate ssl certificate
	 */
	function actionRefresh()
	{
		$result = [];
		
		$data = $this->container->post("data");
		$group_id = (int)(isset($data["group_id"]) ? $data["group_id"] : 0);
		
		$content = "";
		$path_res = "/data/letsencrypt/etc/result";
		$path_res_file = $path_res . "/grp" . $group_id . ".txt";
		if (file_exists($path_res_file))
		{
			$content = file_get_contents($path_res_file);
		}
		
		$result["content"] = $content;
		$result["group_id"] = $group_id;
		$this->api_result->success( $result, "Ok" );
	}
	
}