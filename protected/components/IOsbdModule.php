<?php
interface IOsbdModule
{
	public function getMenu(&$menu, $isAdmin=true);
	public function getVersion();
	public function setVersion($version);
}