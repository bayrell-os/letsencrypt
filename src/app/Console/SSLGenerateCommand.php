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

namespace App\Console;

use App\Docker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Capsule\Manager as DB;
use TinyPHP\Utils;


class SSLGenerateCommand extends Command
{
	protected static $defaultName = 'ssl:generate';

	protected function configure(): void
	{
		$this
			->addArgument('group_id', InputArgument::REQUIRED, 'Group id')
			->setDescription('Generate ssl certificate for group')
			->setHelp('Generate ssl certificate for group')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		
		$group_id = $input->getArgument('group_id');
		\App\SSL::generate_ssl_group_certificate($group_id);
		
		return Command::SUCCESS;
	}
	
}