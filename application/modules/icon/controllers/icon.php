<?php

class Icon extends MX_Controller
{
	private $EmulatorSimpleString = '';
	
	private function getEmulatorString()
	{
		return $this->EmulatorSimpleString;
	}
	
	private function getEmulatorBuild()
	{		
		return false;
	}
	
	private function has_item_template()
	{
		switch ($this->getEmulatorString())
		{
			case 'trinity':
			case 'trinity_tbc':
			case 'trinity_cata':
			case 'skyfire':
			case 'arkcore':
				return true;
		}
		
		return false;
	}
	
	private function no_item_template()
	{
		switch ($this->getEmulatorString())
		{
			case 'trinity_cata_v2':
			case 'trinity_mop':
			case 'trinity_wod':
			case 'trinity_legion':
			case 'trinity_bfa':
			case 'trinity_shadowlands':
				return true;
		}
		
		return false;
	}
	/**
	 * Get an item's icon display name and cache it
	 * @param Int $realm
	 * @param Int $id
	 * @param Int $isDisplayId
	 * @return String
	 */
	public function get($realm = false, $id = false, $isDisplayId = 0)
	{		
		// Check if ID and realm is valid
		if($id != false && is_numeric($id) && $realm != false)
		{
			// Get the emulator string
			$this->EmulatorSimpleString = str_replace(array('_ra', '_soap', '_rbac'), '', $this->realms->getRealm($realm)->getConfig('emulator'));
			
			// It is already a display ID
			if($isDisplayId == 1)
			{
				$icon = $this->getDisplayName($id);
			}
			else
			{
				$displayId = $this->getDisplayId($id, $realm);
				
				if($displayId != false)
				{
					$icon = $this->getDisplayName($displayId, $id);

					if(substr($icon, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf))
					{
						$icon = substr($icon, 3);
					}
				}
				else
				{
					$icon = "inv_misc_questionmark";
				}
			}
			
			die($icon);
		}
	}

	/**
	 * Get the display ID of an item
	 * @param Int $item
	 * @return Int
	 */
	private function getDisplayId($entry, $realm)
	{
		$cache = $this->cache->get("items/item_displayid_".$realm."_".$entry);
		if ($cache)
		{
			return $cache;
		}
		
		$realmObj = $this->realms->getRealm($realm);
		$item = $realmObj->getWorld()->getItem($entry);
		
		if ((!$item || $item == "empty") && $this->getEmulatorString() == 'trinity_cata_v2')
		{
			return $this->getDisplayIdDB_WH($entry, $realm);
		}
		
		if ((!$item || $item == "empty") && $this->getEmulatorString() == 'trinity_wod')
		{
			return $this->getDisplayIdDB_WH($entry, $realm);
		}
		
		if ((!$item || $item == "empty") && $this->getEmulatorString() == 'trinity_legion')
		{
			return $this->getDisplayIdDB_WH($entry, $realm);
		}
		
		if ((!$item || $item == "empty") && $this->getEmulatorString() == 'trinity_bfa')
		{
			return $this->getDisplayIdDB_WH($entry, $realm);
		}
		
		if ((!$item || $item == "empty") && $this->getEmulatorString() == 'trinity_shadowlands')
		{
			return $this->getDisplayIdDB_WH($entry, $realm);
		}
		//Cache the display id
		$this->cache->save("items/item_displayid_".$realm."_".$entry, $item['displayid']);
		
		return $item['displayid'];
	}
	
	private function getDisplayIdDB_WH($entry, $realm)
	{
		$xml = simplexml_load_string(file_get_contents("https://wowhead.com/?item=".$entry."&xml"));
		$DisplayId = $xml->item->icon["displayId"];
		$result = str_replace("[0]","",$DisplayId);
			
		//Cache the display id
		$this->cache->save("items/item_displayid_".$realm."_".$entry, $result);
			
		return $result;
	}
	
	/**
	 * Get the display name from the raxezdev display ID API
	 * @param Int $displayId
	 * @return String
	 */
	private function getDisplayName($displayId, $id)
	{
		$cache = $this->cache->get("items/display_".$displayId);
		
		// Can we use the cache?
		if($cache !== false)
		{
			$name = $cache;
		}
		else
		{
			$icon = $this->getIcon($displayId, $id);
						
			if(!$icon)
			{
				$xml = simplexml_load_string(file_get_contents("https://wowhead.com/?item=".$id."&xml"));
				$xml_name = $xml->item->icon;
				$name = str_replace("[0]","",$xml_name);
			}
			else
			{
				$name = str_replace(' ', '-', strtolower($icon));

				// In case it wasn't found: show ?-icon
				if(empty($name))
				{
					$name = "inv_misc_questionmark";
				}
			}

			// Make sure to cache
			$this->cache->save("items/display_".$displayId, $name);
		}
		
		return $name;
	}
	
	private function getIcon($DisplayId, $id)
	{
		if ($this->no_item_template())		
		{
			$xml = simplexml_load_string(file_get_contents("https://wowhead.com/?item=".$id."&xml"));
			$xml_iconname = $xml->item->icon;
			$iconname = str_replace("[0]","",$xml_iconname);
			return $iconname;
		}
		else if ($this->has_item_template())
		{		
			return false;
		}
	}
	
	/*
	private function findRetailItem($id)
	{
		// Get the item ID
		$query = $this->db->query("SELECT entry FROM item_display WHERE displayid=? LIMIT 1", array($id));

		// Check for results
		if($query->num_rows() > 0)
		{
			$row = $query->result_array();

			return $row[0]['entry'];
		}
		else
		{
			return false;
		}
	}
	
	private function getIconName($item)
	{
		// Get the item XML data
		$xml = file_get_contents("http://www.wowhead.com/item=".$item."&xml");

		// In case it wasn't found: show ?-icon
		if(empty($xml))
		{
			$icon = "inv_misc_questionmark";
		}
		else
		{
			// Convert the data to an array
			$itemData = $this->xmlToArray($xml);

			// Make sure the icon key is set
			$icon = (isset($itemData['item']['icon'])) ? strtolower($itemData['item']['icon']) : "inv_misc_questionmark";
		}

		return $icon;
	}
	*/
	
	/**
	 * Convert XML data to an array
	 * @param String $xml
	 * @return Array
	 */
	private function xmlToArray($xml)
	{
		$xml = simplexml_load_string($xml);
		$json = json_encode($xml);
		$array = json_decode($json, TRUE);

		return $array;
	}
}