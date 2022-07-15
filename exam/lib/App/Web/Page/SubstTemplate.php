<?php

	namespace App\Web\Page;

	/**
	 * Substitutes data on a given string
	 *
	 * This class substitutes a dataset in a given string. It will also execute an if condition if contained in the string <%if condition endif%> and/or create lists 
	 * for a string enclosed between <%=list dataset="name" =%> using the dataset in dataset="name"
	 *
	 * @category   App\Web\Pages
	 * @package    App\Web\Pages\SubstTemplate
	 * @author     Cedric Maenetja 
	 * @copyright  2022 X-Dev
	 * @license    X-Dev
	 * @version    Release: @package_version@
	 */ 

	trait SubstTemplate
	{
		/**
		 * Substitutes data on a given string
		 *
		 * @param string  	$string The string to do substitutions on
		 * @param array 	$data 	The dataset to substitute on the string
		 * 
		 * @author Cedric Maenetja 
		 * @return String Substituted string or error
		 */ 

		public static function GetSubstitutedString ($string, $data)
		{
			$string = self::SubstListData ($string, $data);
			foreach (self::loadWithGenerator ($data) as $key => $value)
			{
				if (is_array ($value))
				{
					foreach (self::loadWithGenerator ($value) as $nestedArray)
					{
						$string = (! is_array ($nestedArray)) ? self::ExecuteÌfStatements ($string, $value) : self::ExecuteÌfStatements ($string, $nestedArray);
					}
				}
			}

			return self::SubstStringData ($string, $data);
		}


		/**
		 * Executes if conditions
		 *
		 * @param String  	$string The string to do substitutions on
		 * @param array 	$data 	The dataset to substitute on the string
		 * 
		 * @author Cedric Maenetja 
		 * @return String Substituted string or error
		 */ 

		private static function ExecuteÌfStatements ($string, $data)
		{
			$charArray = array ('{' => '\{', '}' => '\}', '.' => '\.', '(' => '\(', ')' => '\)', '"' => '\"');
			
			if (preg_match_all ('/<%if (.*\))/', $string, $listsOfConditions))
			{
				for ($i = 0; $i < count ($listsOfConditions[0]); $i++)
				{
					$id = $listsOfConditions[1][0];
					$conditions = $listsOfConditions[0][0];
					//echo strpos ($string, $conditions)."\ns";
					
					for ($h = 0; $h < count ($listsOfConditions[0]); $h++)
					{
						$htmlList = $listsOfConditions[0][$h];
						$htmlList = self::SubstStringData ($htmlList, $charArray);
						
						if (preg_match ("/$htmlList([^\"]+?)endif%>/", $string, $listSection))
						{
							$conditionToExecute = self::SubstStringData ($htmlList, array ('<%if' => 'if', '\\' => '', '"' => '\''));
							$conditionToExecute = self::SubstStringData ($conditionToExecute, $data)."return '$listSection[1]'";
							
							try
							{
								$text = (strpos ($conditionToExecute, '(\'{') === false) ? eval ("$conditionToExecute;") : $listSection[0];
							}
							catch (Exception $e)
							{
								return sprintf ('failed to execute %s', $htmlList);
							}
							
							$string = str_replace ($listSection[0], $text, $string);
						}
					}
				}
			}

			return $string;
		}


		/**
		 * Populates a list
		 *
		 * @param String  	$string The string to do substitutions on
		 * @param array 	$data 	The dataset to substitute on the string
		 * 
		 * @author Cedric Maenetja 
		 * @return String Substituted string or error
		 */ 

		private static function SubstListData ($string, $data)
		{
			if (preg_match_all ('/<%=list dataset="([^"]+?)"/', $string, $listsInString))
			{
				for ($i = 0; $i < count ($listsInString[0]); $i++)
				{
					$id = $listsInString[1][0];
					$list = $listsInString[0][0];

					if (preg_match_all ("/$list/", $string, $totalListsById))
					{
						if (count ($totalListsById[0]) > 1) return sprintf ('Duplicate list id [%s]', $id);
					}
					
					if (! isset ($data[$id])) return sprintf ('Array [%s] not defined', $id);
					
					for ($h = 0; $h < count ($listsInString[0]); $h++)
					{
						$htmlList = $listsInString[0][$h];
						$listContent = '';
						
						if (preg_match ("/$htmlList([^\"]+?)(.*)([^\"]+?)=%>/", $string, $listSection))
						{	
							$conditionSection = $listSection[2].$listSection[3];
							
							foreach (self::loadWithGenerator ($data[$listsInString[1][$h]]) as $dataArray)
							{
								$textToSubstitute = self::ExecuteÌfStatements ($conditionSection, $dataArray);
								if (! empty ($textToSubstitute))
								{
									$listContent .= self::SubstStringData ($textToSubstitute, $dataArray);
								}
							}
							
							$string = str_replace ($listSection[0], $listContent, $string);
						}
						
						if (preg_match ("/$htmlList(.|\n|)*(=%>)$/", $string, $listSection))
						{
							if (! isset ($data[$listsInString[1][$h]])) return sprintf ('list [%s] not found', $listsInString[1][$h]);
							
							foreach (self::loadWithGenerator ($data[$listsInString[1][$h]]) as $itemArray)
							{
								$listContent .= self::SubstStringData (trim ($listSection[1]), $itemArray);
							}
							
							$string = str_replace ($listSection[0], $listContent, $string);
						}
					}
				}
			}
		
			return $string;
		}



		/**
		 * Substitutes strings
		 *
		 * @param String  	$string The string to do substitutions on
		 * @param array 	$data 	The dataset to substitute on the string
		 * 
		 * @author Cedric Maenetja 
		 * @return String Substituted string or error
		 */ 

		private static function SubstStringData ($templateString, $data)
		{
			if (! is_array ($data)) return $templateString;
			
			foreach (self::loadWithGenerator ($data) as $key => $value)
			{
				$templateString = (is_array ($value)) ? self::SubstStringData ($templateString, $value) : str_replace ($key, $value, $templateString);
			}
		
			# return the substituted string
			return $templateString;
		}



		/**
		 * PHP Generator
		 *
		 * @param array 	$arr 	The array to yield
		 * 
		 * @author Cedric Maenetja 
		 * @return array yield element
		 */ 

		private static function loadWithGenerator ($arr)
		{
			foreach ($arr as $key => $value)
			{
				yield $key => $value;
			}
		}
	}

?>