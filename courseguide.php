<?php

# Class to create an online courseguide system
class courseguide extends frontControllerApplication
{
	# Function to assign defaults additional to the general application defaults
	function defaults ()
	{
		# Specify available arguments as defaults or as NULL (to represent a required argument)
		$defaults = array (
			'applicationName' => 'Course guide',
			'div' => strtolower (__CLASS__),
			'administrators' => true,
			'hostname' => 'localhost',
			'database' => 'courseguide',
			'username' => 'courseguide',
			'password' => NULL,
			'table' => false,
			'databaseStrictWhere' => true,
			'tabUlClass' => 'tabsflat',
			'jQuery' => true,
			'usersAutocomplete' => false,
			'userIsStaffCallback' => 'userIsStaffCallback',		// Callback function
			'userNameCallback' => 'userNameCallback',			// Callback function
			'richtextEditorAreaCSS' => array (),
			'richtextEditorConfig.bodyClass' => false,
		);
		
		# Return the defaults
		return $defaults;
	}
	
	# Class constants
	const EMPTY_TEXT = '-';		// Richtext values containing only this (after removing HTML tags) are considered empty - used as a method to bypass required field requirement
	
	
	# Function to assign supported actions
	function actions ()
	{
		# Define available tasks
		$actions = array (
			'home' => array (
				'tab' => 'Home',
				'description' => false,
				'url' => ($this->academicYear ? $this->academicYear . '/' : ''),
				'icon' => 'house',
				'yearContext' => true,
			),
			'edit' => array (
				'description' => 'Editing',
				'tab' => 'Editing',
				'url' => 'edit/' . ($this->academicYear ? $this->academicYear . '/' : ''),
				'icon' => 'pencil',
				'yearContext' => true,
				'enableIf' => $this->academicYearEditingEnabled && $this->userHasEditableSections,
			),
			'cloneyear' => array (
				'description' => 'Clone to new year&hellip;',
				'subtab' => 'Clone to new year&hellip;',
				'parent' => 'admin',
				'url' => 'cloneyear.html',
				'icon' => 'page_copy',
				'administrator' => true,
			),
		);
		
		# Return the actions
		return $actions;
	}
	
	
	# Database structure definition
	public function databaseStructure ()
	{
		return "
			
			-- Administrators
			CREATE TABLE `administrators` (
			  `username__JOIN__people__people__reserved` varchar(191) NOT NULL COMMENT 'Username',
			  `active` enum('','Yes','No') NOT NULL DEFAULT 'Yes' COMMENT 'Currently active?',
			  `receiveEmail` enum('','Yes','No') NOT NULL DEFAULT 'Yes' COMMENT 'Receive e-mail notifications?',
			  `privilege` enum('Administrator','Restricted administrator') NOT NULL DEFAULT 'Administrator' COMMENT 'Administrator level',
			  PRIMARY KEY (`username__JOIN__people__people__reserved`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System administrators';
			
			-- Field trips
			CREATE TABLE `fieldtrips` (
			  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `nodeId` int NOT NULL COMMENT 'Node',
			  `staff` varchar(255) NOT NULL COMMENT 'Staff',
			  `datesRichtext` mediumtext NOT NULL COMMENT 'Dates',
			  `mainRichtext` mediumtext NOT NULL COMMENT 'Aims, objectives and information',
			  `editedBy` varchar(255) NOT NULL COMMENT 'Edited by',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Saved at',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table of field trips';
			
			-- Modules
			CREATE TABLE `modules` (
			  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `nodeId` int NOT NULL COMMENT 'Node',
			  `coordinator` varchar(255) NOT NULL COMMENT 'Section co-ordinator',
			  `contributor` varchar(255) NOT NULL COMMENT 'Contributor',
			  `mainRichtext` mediumtext NOT NULL COMMENT 'Main text',
			  `lecturesRichtext` mediumtext COMMENT 'Lectures',
			  `readingsRichtext` mediumtext NOT NULL COMMENT 'Key readings',
			  `editedBy` varchar(255) NOT NULL COMMENT 'Edited by',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Saved at',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Modules';
			
			-- Main table of nodes representing the course structure
			CREATE TABLE `nodes` (
			  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `academicYear` varchar(9) NOT NULL COMMENT 'Academic year',
			  `name` varchar(255) NOT NULL COMMENT 'Title',
			  `type` varchar(255) NOT NULL COMMENT 'Type',
			  `moniker` varchar(40) NOT NULL COMMENT 'Web address',
			  `parentId` int NOT NULL COMMENT 'Within',
			  `ordering` int NOT NULL DEFAULT '5' COMMENT 'Order (1=earliest)',
			  `pageBreakBefore` int DEFAULT NULL COMMENT 'Page break before?',
			  `editors` varchar(255) DEFAULT NULL COMMENT 'Editable by',
			  `currentlyWith` enum('Editors','Administrators') NOT NULL DEFAULT 'Editors' COMMENT 'Control currently with',
			  `status` enum('Draft','Finalised') NOT NULL DEFAULT 'Draft' COMMENT 'Finalisation status of entry',
			  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Structure of courses';
			
			-- Optional papers
			CREATE TABLE `optionalpapers` (
			  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `nodeId` int NOT NULL COMMENT 'Node',
			  `coordinator` varchar(255) NOT NULL COMMENT 'Section co-ordinator',
			  `contributors` varchar(255) DEFAULT NULL COMMENT 'Contributors',
			  `overviewRichtext` mediumtext NOT NULL COMMENT 'Overview',
			  `lecturesRichtext` mediumtext COMMENT 'Lectures',
			  `readingsRichtext` mediumtext NOT NULL COMMENT 'Key readings',
			  `timetablingRichtext` mediumtext COMMENT 'Timetabling',
			  `modeOfAssessmentRichtext` mediumtext NOT NULL COMMENT 'Mode of assessment',
			  `fieldtripsPracticalsRichtext` mediumtext COMMENT 'Field trips / practicals',
			  `supervisionsRichtext` mediumtext NOT NULL COMMENT 'Supervisions',
			  `editedBy` varchar(255) NOT NULL COMMENT 'Edited by',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Saved at',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table of optional papers';
			
			-- Pages
			CREATE TABLE `pages` (
			  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `nodeId` int NOT NULL COMMENT 'Node',
			  `pageRichtext` mediumtext NOT NULL COMMENT 'Text of page',
			  `editedBy` varchar(255) NOT NULL COMMENT 'Edited by',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Saved at',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table of pages';
			
			-- Papers
			CREATE TABLE `papers` (
			  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `nodeId` int NOT NULL COMMENT 'Node',
			  `coordinator` varchar(255) NOT NULL COMMENT 'Section co-ordinator',
			  `contributors` varchar(255) DEFAULT NULL COMMENT 'Contributors',
			  `overviewRichtext` mediumtext NOT NULL COMMENT 'Overview',
			  `readingsRichtext` mediumtext NOT NULL COMMENT 'General readings',
			  `modules` varchar(255) DEFAULT NULL COMMENT 'Modules',
			  `timetablingRichtext` mediumtext COMMENT 'Timetabling',
			  `modeOfAssessmentRichtext` mediumtext NOT NULL COMMENT 'Mode of assessment',
			  `fieldtripsPracticalsRichtext` mediumtext COMMENT 'Field trips / practicals',
			  `supervisionsRichtext` mediumtext NOT NULL COMMENT 'Supervisions',
			  `editedBy` varchar(255) NOT NULL COMMENT 'Edited by',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Saved at',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Papers';
			
			-- Settings
			CREATE TABLE `settings` (
			  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Automatic key (ignored)',
			  `academicYearEarliestEditable` varchar(255) DEFAULT NULL COMMENT 'Earliest editable academic year (earlier years locked)',
			  `visibleToStudents` mediumtext COMMENT 'Sections/years visible to students',
			  `academicYearStartsMonth` int NOT NULL DEFAULT '8' COMMENT '''Current'' year starts on month',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Settings';
		";
	}
	
	
	# Additional processing, run before actions is processed
	function mainPreActions ()
	{
		# Current academic year for today's date
		$this->currentAcademicYear = timedate::academicYear ($this->settings['academicYearStartsMonth'], true, true);
		
		# Parse out the settings for sections visible to students
		$this->visibleToStudents = $this->settings['visibleToStudents'] ? explode ("\n", str_replace ("\r\n", "\n", $this->settings['visibleToStudents'])) : array ();
		
		# Get the academic years
		$this->academicYears = $this->academicYears ();
		
		# Set the selected academic year, when in year context (i.e. not when viewing pages like feedback/admin)
		$this->academicYear = false;
		$this->academicYearUrlMoniker = false;
		#if (isSet ($this->actions[$this->action]['yearContext'])) {
		if (isSet ($_GET['year'])) {
			$this->academicYear = $this->selectedAcademicYear ();
			$this->academicYearUrlMoniker = $this->academicYearUrlMoniker ($this->academicYear);
			//$this->academicYearUrlMoniker = ($this->action == 'home' && ($this->academicYear == $this->currentAcademicYear) ? 'current' : $this->academicYear);
		}
		
		# Determine if editing of this academic year's data is enabled
		$this->academicYearEditingEnabled = $this->academicYearEditingEnabled ();
		
		# Get the metadata; this is the flat structure version
		$this->metadata = $this->getMetadata ($this->academicYear);
	}
	
	
	# Function to obtain the URL moniker for an academic year
	private function academicYearUrlMoniker ($academicYear)
	{
		# For the current academic year, use 'current'
		if ($academicYear == $this->currentAcademicYear) {
			return 'current';
		}
		
		# Otherwise as normal
		return $academicYear;
	}
	
	
	# Function to determine if editing of this academic year's data is enabled
	private function academicYearEditingEnabled ()
	{
		# Enabled if no locking
		if (!$this->settings['academicYearEarliestEditable']) {return true;}
		
		# Create a list of locked years
		$lockedYears = array ();
		foreach ($this->academicYears as $academicYear) {
			if ($academicYear == $this->settings['academicYearEarliestEditable']) {
				break;	// Stop when earliest year found
			}
			$lockedYears[] = $academicYear;
		}
		
		# If the current academic year is a locked year, disable editing
		if (in_array ($this->academicYear, $lockedYears)) {
			return false;
		}
		
		# Editing is enabled
		return true;
	}
	
	
	# Function to determine if a section is visible to students
	private function visibleToStudents ($academicYear, $containerMoniker)
	{
		return (in_array ($academicYear . '_' . $containerMoniker, $this->visibleToStudents));
	}
	
	
	# Date switcher
	function guiSearchBox ()
	{
		# Show the droplist
		return $this->yearSelectionDroplist ();
	}
	
	
	# Additional processing
	function main ()
	{
		# For the academic year moniker, if in editing mode, do not permit 'current'
		if ($this->action == 'edit') {
			$this->academicYearUrlMoniker = $this->academicYear;
		}
		
		# Set a cookie for the selected academic year if not already present or different
		if ($this->academicYear) {
			if (!isSet ($_COOKIE['academicyear']) || ($_COOKIE['academicyear'] != $this->academicYear)) {
				$sevenDays = 7 * 24 * 60 * 60;
				setcookie ('academicyear', $this->academicYear, time () + $sevenDays, $this->baseUrl . '/', $_SERVER['SERVER_NAME']);
			}
		}
		
		# If a cookie is set, and no academic year is set, redirect to the academic year in the cookie, except in built-in actions
		if (isSet ($this->actions[$this->action]['yearContext'])) {
			if (!$this->academicYear) {
				if (isSet ($_COOKIE['academicyear'])) {
					if (in_array ($_COOKIE['academicyear'], $this->academicYears)) {
						$redirectTo = $this->academicYearToUrl ($_COOKIE['academicyear']);
						echo application::sendHeader (302, $_SERVER['_SITE_URL'] . $redirectTo, true);
						return false;
					}
				}
			}
		}
		
		# Get the container items for the current year (e.g. part1a, part1b, part2)
		$this->containers = $this->getContainers ($this->academicYear);
		
		# Get the current container
		$this->container = $this->getContainer ();
		
		# For the home action, end if invalid container
		if ($this->action == 'home') {
			if ($this->container === false) {
				$this->page404 ();
				return false;
			}
		}
		
		# Determine if the current user is staff (which includes added-in users)
		if (!$this->userIsStaff = $this->userIsStaff ()) {
			if ($this->academicYearEditingEnabled) {
				$html  = "\n<p>Sorry, the academic year {$this->academicYear} is currently only visible to staff.</p>";
				$html .= "\n<p>You can select another academic year:</p>";
				$html .= $this->yearSelectionDroplist ();
				echo $html;
				return false;
			}
		}
		
		# Get the type definitions
		$this->types = $this->getTypes ();
		
		# Get the entries for this academic year
		$this->entries = $this->getEntries (array_keys ($this->metadata));
		
		# Attach into each section of the metadata whether there is an entry
		foreach ($this->metadata as $id => $entry) {
			$this->metadata[$id]['_hasEntry'] = (isSet ($this->entries[$entry['type']][$id]));
		}
		
		# Set the root node; by default this is automatic as the top of the tree, but the container limitation can set it
		$rootNodeId = ($this->container ? $this->containers[$this->container]['id'] : false);
		
		# Get the course structure
		$this->structure = $this->getStructure ($rootNodeId);
		
		# Run a second parse of the metadata for whether the current user can edit
		$this->structure = $this->attachEditableAreasFlag ($this->structure);
		
	}
	
	
	# Function to determine if the user is a member of staff
	private function userIsStaff ()
	{
		# Not staff if not logged in
		if (!$this->user) {return false;}
		
		# Determine if the user is staff from the callback function
		$userIsStaffCallbackFunction = $this->settings['userIsStaffCallback'];
		$userIsStaff = $userIsStaffCallbackFunction ($this->user);
		return $userIsStaff;
	}
	
	
	# Function to create a year selection droplist
	private function yearSelectionDroplist ()
	{
		# Get the list of years
		$academicYearList = $this->academicYearList ();
		
		# Selected year (as URL)
		$selected = $this->academicYearToUrl ($this->academicYear);
		
		# Compile as HTML
		$html = application::htmlJumplist ($academicYearList, $selected, $action = '', $name = 'jumplist', $parentTabLevel = 0, $class = 'jumplist noprint', $introductoryText = 'Academic year:');
		
		# Add 'Printed at' date
		$html .= "\n<p class=\"printedat\">Printed at: " . date ('g:ia, jS F Y') . '</p>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create a listing of academic years
	private function academicYearList ()
	{
		# Add a switcher for the years
		$academicYears = array ();
		foreach ($this->academicYears as $academicYear) {
			$url = $this->academicYearToUrl ($academicYear);
			$academicYears[$url] = $academicYear;
			if ($academicYear == $this->currentAcademicYear) {
				$academicYears[$url] .= ' (current)';
			}
		}
		
		# Return the list
		return $academicYears;
	}
	
	
	# Function to convert an academic year string to a URL path
	private function academicYearToUrl ($academicYear)
	{
		return $this->baseUrl . '/' . ($this->action == 'edit' ? 'edit/' : '') . ($this->action == 'home' && ($academicYear == $this->currentAcademicYear) ? 'current' : $academicYear) . '/';
	}
	
	
	# Function to get the container entries for the supplied academic year
	private function getContainers ($academicYear = false)
	{
		# Get the data
		if ($academicYear) {
			$conditions['academicYear'] = $academicYear;
		}
		$conditions['type'] = 'container';
		$data = $this->databaseConnection->select ($this->settings['database'], 'nodes', $conditions, array (), true, $orderBy = 'name');
		
		# Rearrange the data as moniker => container
		$containers = array ();
		foreach ($data as $nodeId => $container) {
			$moniker = $container['moniker'];
			$academicYearUrlMoniker = $this->academicYearUrlMoniker ($academicYear);
			$containers[$moniker] = array (
				'id' => $nodeId,
				'url' => "{$this->baseUrl}/{$academicYearUrlMoniker}/{$container['moniker']}/",
				'name' => $container['name'],
			);
		}
		
		# Return the list
		return $containers;
	}
	
	
	# Function to get the current container
	private function getContainer ()
	{
		# No container if not set
		if (!isSet ($_GET['container'])) {return NULL;}
		
		# Throw 404 if the container is not valid
		if (!array_key_exists ($_GET['container'], $this->containers)) {return false;}
		
		# Otherwise return the validated container
		return $_GET['container'];
	}
	
	
	# Function to get the metadata model data
	private function getMetadata ($academicYear)
	{
		# Get the metadata
		$conditions = array ('academicYear' => $academicYear);
		$metadata = $this->databaseConnection->select ($this->settings['database'], 'nodes', $conditions, array (), true, $orderBy = 'parentId,ordering,name');
		
		# Add to the metadata whether the current user can edit
		$metadata = $this->attachEditableAreasFlag ($metadata);
		
		# Return the data
		return $metadata;
	}
	
	
	# Iterative function to attach the flag to each metadata area for whether the current user can edit
	private function attachEditableAreasFlag ($metadata, $isWithinIteration = false, $inheritedEditors = array ())
	{
		# Start a array (acts also as a counter), at the first entry point into this iterative routine only
		if (!$isWithinIteration) {
			$this->userHasEditableSections = array ();
		}
		
		# Loop through each area
		foreach ($metadata as $nodeId => $entry) {
			
			# Get the editors list for the current node
			$directEditors = $this->editorsList ($entry['editors']);
			$metadata[$nodeId]['_directEditors'] = $directEditors;
			
			# Mark the inherited editors
			$metadata[$nodeId]['_inheritedEditors'] = $inheritedEditors;
			$editors = array_merge ($directEditors, $inheritedEditors);
			
			# Set editability, and also attach CSS classes
			$editable = ($this->academicYearEditingEnabled && $this->user && (in_array ($this->user, $editors) || $this->userIsAdministrator));
			$metadata[$nodeId]['_editable'] = $editable;
			$classes = array ();
			if ($editable && !$this->userIsAdministrator) {
				$classes[] = 'usereditable';
			}
			$classes[] = ($entry['status'] == 'Finalised' ? 'bulletgreen' : ($entry['currentlyWith'] == 'Administrators' ? 'bulletorange' : 'bulletgraysquare'));
			$metadata[$nodeId]['_class'] = implode (' ', $classes);
			
			# Copy the attached data fields to the flattened version of the data (which may actually be the current source of the loop, though it then gets overwritten at the end of the overall iteration)
			#!# This is very hacky, but at least keepts the flattened version in sync
			$this->metadata[$nodeId]['_directEditors'] = $metadata[$nodeId]['_directEditors'];
			$this->metadata[$nodeId]['_inheritedEditors'] = $metadata[$nodeId]['_inheritedEditors'];
			$this->metadata[$nodeId]['_editable'] = $metadata[$nodeId]['_editable'];
			$this->metadata[$nodeId]['_class'] = $metadata[$nodeId]['_class'];
			
			# Increment the counter
			if ($editable) {
				$this->userHasEditableSections[] = $nodeId;
			}
			
			# Iterate, if child information has yet been attached
			if (isSet ($entry['_children'])) {
				if ($entry['_children']) {
					$metadata[$nodeId]['_children'] = $this->attachEditableAreasFlag ($entry['_children'], true, $editors);
				}
			}
		}
		
		# Return the data
		return $metadata;
	}
	
	
	# Helper function to get the course structure hierarchy
	private function getStructure ($fromRootNodeId = false, &$errorHtml = false)
	{
		# Load support for managing a hierarchical structure
		$this->hierarchy = new hierarchy ($this->metadata, $fromRootNodeId);
		
		#!# Natsort the data
		
		# End if no metadata
		if (!$this->metadata) {
			return array ();
		}
		
		# Get the hierarchy
		if (!$data = $this->hierarchy->getHierarchy ()) {
			$errorHtml = 'Error: ' . $this->hierarchy->getError ();
			return false;
		}
		
		# Apply URL prefixing through the tree
		$data = $this->attachUrlLocations ($data);
		
		# Return the hierarchy
		return $data;
	}
	
	
	# Iterative function to attach URL locations through the tree
	private function attachUrlLocations ($data, $prefix = '')
	{
		# Work through the entries at the current node level
		foreach ($data as $nodeId => $metadata) {
			
			//# Set the prefix for the current item
			//$data[$nodeId]['urlPrefix'] = $prefix;
			
			# If the current entry is itself a container, remove prefixing
			#!# Currently no support for nesting of containers
			if ($metadata['type'] == 'container') {
				$prefix = '';
			}
			
			# Add edit URL
			$data[$nodeId]['_editUrl'] = "/edit/{$this->academicYear}/" . $prefix . "{$metadata['moniker']}/";	// Never uses 'current'
			
			# Add a general URL which applies in the current action context
			if ($this->action == 'edit') {
				$data[$nodeId]['_url'] = $data[$nodeId]['_editUrl'];
			} else {
				$data[$nodeId]['_url'] = "/{$this->academicYearUrlMoniker}/" . $prefix . "{$metadata['moniker']}/";
			}
			
			# Determine the prefix value for each child
			if ($metadata['type'] == 'container') {
				$prefix = $metadata['moniker'] . '/';
			}
			
			# Iterate
			if ($metadata['_children']) {
				$data[$nodeId]['_children'] = $this->attachUrlLocations ($metadata['_children'], $prefix);
			}
		}
		
		# Return the modified data
		return $data;
	}
	
	
	# Function to define the types
	private function getTypes ()
	{
		# Define the types
		$types = array (
			'page' => array (
				'table' => 'pages',
				'label' => 'Page',
			),
			'paper' => array (
				'table' => 'papers',
				'label' => 'Paper',
				'likelyChild' => 'module',
			),
			'optionalpaper' => array (
				'table' => 'optionalpapers',
				'label' => 'Optional paper',
				'likelyChild' => 'module',
			),
			'module' => array (
				'table' => 'modules',
				'label' => 'Module',
			),
			'fieldtrip' => array (
				'table' => 'fieldtrips',
				'label' => 'Fieldtrip',
			),
			'container' => array (
				'table' => false,
				'label' => '(Main container)',
				'template' => '<p>(This entry is only a container for other entries, so has no content.)</p>',
				'fields' => array (),
				'likelyChild' => 'paper',
			),
			'structure' => array (
				'table' => false,
				'label' => '(Structure)',
				'template' => '<p>(This entry only provides a top-level structure.)</p>',
				'fields' => array (),
				'likelyChild' => 'container',
			),
			'title' => array (
				'table' => false,
				'label' => '(Title)',
				'template' => '<p>(This entry is only a container for other entries, so has no content.)</p>',
				'fields' => array (),
				'likelyChild' => 'paper',
			),
		);
		
		# Add in fields for types driven by a database table structure
		foreach ($types as $type => $attributes) {
			if ($attributes['table']) {
				$types[$type]['fields'] = $this->databaseConnection->getFields ($this->settings['database'], $attributes['table']);
			}
		}
		
		# Assemble the templates
		$internalFields = array ('id', 'nodeId', 'editedBy', 'savedAt', );
		foreach ($types as $type => $attributes) {
			if ($attributes['table']) {
				$types[$type]['template'] = '';
				foreach ($types[$type]['fields'] as $field => $attributes) {
					if (in_array ($field, $internalFields)) {continue;}		// Skip internal fields
					$types[$type]['template'] .= "\n" . '{heading:' . $field . '}';
					$types[$type]['template'] .= "\n";
					if (substr_count (strtolower ($attributes['Type']), 'varchar')) {$types[$type]['template'] .= '<p>';}
					$types[$type]['template'] .= '{' . $field . '}';
					if (substr_count (strtolower ($attributes['Type']), 'varchar')) {$types[$type]['template'] .= '</p>';}
				}
			}
		}
		
		# Return the types
		return $types;
	}
	
	
	# Function to get the list of years
	private function academicYears ()
	{
		# Start a list of academic years
		$academicYears = array ();
		
		# Get the current year
		$current = timedate::academicYear (9);
		$academicYears[] = $current;
		
		# Add the previous
		// $academicYears[] = $current - 1;
		
		# Add next year and following year
		$academicYears[] = $current + 1;
		$academicYears[] = $current + 2;
		
		# Format year as range, so "2014" becomes "2014-15", if required
		foreach ($academicYears as $index => $academicYear) {
			$academicYears[$index] = $academicYear . '-' . substr (($academicYear + 1), -2);
		}
		
		# Add in any existing years in the table
		$academicYearsInUse = $this->academicYearsInUse ();
		$academicYears = array_merge ($academicYears, $academicYearsInUse);
		$academicYears = array_unique ($academicYears);
		
		# Sort the list
		sort ($academicYears);
		
		# Return the list
		return $academicYears;
	}
	
	
	# Function to get years having node entries
	private function academicYearsInUse ()
	{
		$query = "SELECT DISTINCT academicYear FROM {$this->settings['database']}.nodes ORDER BY academicYear;";
		$academicYearsInUse = $this->databaseConnection->getPairs ($query);
		return $academicYearsInUse;
	}
	
	
	
	# Select the current academic year
	private function selectedAcademicYear ()
	{
		# If no year is specified return false
		if (!isSet ($_GET['year'])) {
			return false;
		}
		$year = $_GET['year'];
		
		# Look up the year for the keyword 'current' (which is intended to enable permalinks from other parts of the site)
		if ($year == 'current') {
		#if (($this->action == 'home') && ($year == 'current')) {
			$year = $this->currentAcademicYear;
		}
		
		# Ensure the year is registered
		if (!in_array ($year, $this->academicYears)) {
			$this->page404 ();
			return false;
		}
		
		# Return the year
		return $year;
	}
	
	
	# Home page
	public function home ()
	{
		# Start the HTML
		$html = '';
		
		# Require a year
		if (!$this->academicYear) {
			$html  = "\n<h2>Course guide</h2>";
			$html .= "\n<p>Welcome to the online course guide.</p>";
			$html .= $this->academicYearSelectionList ();
			echo $html;
			return true;
		}
		
		# Heading
		$html  = "\n<h2>Course guide {$this->academicYear}</h2>";
		
		//There are no sections so far. You may wish to <a href=\"{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/add.html\">add one</a>.
		
		# Get the hierarchy
		if (!$this->structure) {
			$html .= $this->yearSelectionDroplist ();
			$html .= "\n<p>There is no information for {$this->academicYear} yet.</p>";
			echo $html;
			return false;
		}
		
		# Require a container to be selected
		if (!$this->container) {
			$list = array ();
			foreach ($this->containers as $containerMoniker => $container) {
				$visibleToStudents = $this->visibleToStudents ($this->academicYear, $containerMoniker);
				$visibleToUser = ($visibleToStudents || $this->userIsAdministrator);
				if ($visibleToUser) {
					$list[] = "<a href=\"{$container['url']}\">" . htmlspecialchars ($container['name']) . '</a>' . (!$visibleToStudents ? '<span class="warning"> (Not visible to students)</span>' : '');
				} else {
					#!# Should be not "currently" then "yet" when in/after current year
					$list[] = '<span class="comment">' . htmlspecialchars ($container['name']) . ' (Not yet available to students)</span>';
				}
			}
			$html .= $this->yearSelectionDroplist ();
			$html .= "\n<p>Please select a section to view:</p>";
			$html .= application::htmlUl ($list);
			if (isSet ($this->actions['edit'])) {
				$html .= "\n<h2>View/edit sections</h2>";
				$html .= "\n<p class=\"inpageeditingbutton\"><a class=\"actions\" href=\"{$this->baseUrl}/edit/{$this->academicYear}/\"><img src=\"/images/icons/pencil.png\" alt=\"\" class=\"icon\" /> <strong>View/edit your sections</strong></a></p>";
			}
			echo $html;
			return false;
		}
		
		# End if this part not visible to students
		if (!$this->userIsAdministrator) {
			if (!$this->visibleToStudents ($this->academicYear, $this->container)) {
				$this->page404 ();
				return false;
			}
		}
		
		# Reset the heading to show the section
		$html  = "\n<h2>" . htmlspecialchars ($this->containers[$this->container]['name']) . " course guide ({$this->academicYear}):</h2>";
		
		# Show only the current node (including any submodules if required
		if (isSet ($_GET['moniker'])) {
			$container = (isSet ($_GET['container']) ? $_GET['container'] : false);
			$moniker = $_GET['moniker'];
			if (!$nodeId = $this->getActualId ($moniker, $container, $this->academicYear)) {
				$this->page404 ();
				return false;
			}
			$structureFromNode = $this->getStructure ($nodeId);
			# Manually generate the _editUrl because the higher level is not available so there are no parents to transfer the container from
			#!# This is rather hacky and ideally getStructure would handle this somehow natively
			$structureFromNode[$nodeId]['_editUrl'] = "/edit/{$this->academicYear}/" . ($container ? $container . '/' : '') . "{$moniker}/";	// Never uses 'current'
			$html .= "\n" . '<div class="contentsection">';
			$html .= $this->combineAll ($structureFromNode, $level = 1, false);
			$html .= "\n</div>";
			$this->renderPageHtmlPdf ($html, array ($this->academicYear, $this->container, $moniker));
			return true;
		}
		
		# Otherwise, show the hierarchy list
		$html .= "\n<div class=\"contextbox viewable noprint\">";
		$html .= hierarchy::asUl ($this->structure, $this->baseUrl);
		$html .= "\n</div>";
		
		# Show as full page if required
		$html .= "\n" . '<div class="contentsection">';
		$html .= $this->combineAll ($this->structure);
		$html .= "\n</div>";
		
		# Show the HTML (or export)
		$this->renderPageHtmlPdf ($html, array ($this->academicYear, $this->container));
	}
	
	
	# Function to render the course guide display as a PDF
	private function renderPageHtmlPdf ($html, $pathComponentsArray)
	{
		# Determine if PDF export is wanted
		$pdfExport = (isSet ($_GET['export']) && $_GET['export'] == 'pdf');
		
		# Determine the location of the page
		$location = '/' . implode ('/', $pathComponentsArray) . '/';
		$pdfFilename = 'courseguide_' . implode ('_', $pathComponentsArray) . '.pdf';
		
		# Show HTML if not PDF
		if (!$pdfExport) {
			$html = "\n<p class=\"actions pdflink noprint right\"><a href=\"{$this->baseUrl}{$location}{$pdfFilename}\">PDF (for printing/saving)</a></p>" . $html;
			echo $html;
			return true;
		}
		
		# Compile the HTML
		$introductionHtml = "\n<p class=\"comment\">Printed at " . date ('g:ia, jS F Y') . " from {$_SERVER['_SITE_URL']}{$this->baseUrl}{$location}</p>\n<hr />";
		
		# Compile the HTML
		$pdfHtml = $introductionHtml . "\n<div id=\"{$this->settings['div']}\">" . $html . "\n</div>";
		$pdfHtml = "<head><base href=\"{$_SERVER['_SITE_URL']}/\"></head>" . $pdfHtml;
		
		# Render
		//$tempFilename = application::generatePassword (20) . '.pdf';	// Generate a random filename for the temporary file
		application::html2pdf ($pdfHtml, $pdfFilename);	// This should match the URL filename, as it will be exposed when doing a download after viewing in an embedded viewer (e.g. Chrome's PDF viewer)
	}
	
	
	# Function to get the entries for the specified academic year
	private function getEntries ($nodeIds)
	{
		# Start an array of entries
		$entries = array ();
		
		# Get data for each object type
		foreach ($this->types as $type => $attributes) {
			
			# Skip types that have no table
			if (!$attributes['table']) {continue;}
			
			# Construct a query to extract the highest-numbered items in the table; see: http://stackoverflow.com/a/1313293
			$query = "
				SELECT items1.*
				FROM {$this->settings['database']}.{$attributes['table']} AS items1
				LEFT JOIN {$this->settings['database']}.{$attributes['table']} AS items2 ON (items1.nodeId = items2.nodeId AND items1.id < items2.id)
				WHERE
					    items2.id IS NULL
					AND items1.nodeId IN(" . implode (',', $nodeIds) . ")
			;";
			$entries[$type] = $this->databaseConnection->getData ($query, 'nodeId');	// Index on nodeId so this can be easily looked-up
		}
		
		# Return the entries
		return $entries;
	}
	
	
	# Recursive function to combine the course guide into a single page
	private function combineAll ($structure, $level = 1, $showInPageLink = true, /* private */ $nextHeadingHasPageBreak = false)
	{
		# Start the HTML
		$html = '';
		
		# Loop through each data item
		foreach ($structure as $id => $substructure) {
			
			# Show the entry (which includes replacing the submodule placeholder if in a course)
			$html .= $this->showEntry ($substructure, $level, $showInPageLink, $nextHeadingHasPageBreak);
			
			# Enable page breaks after first
			$nextHeadingHasPageBreak = true;
			
			# If there are a children, process those
			if ($substructure['_children']) {
				$html .= $this->combineAll ($substructure['_children'], $level++, $showInPageLink);		// Recurse
			}
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to show an individual entry (which includes replacing the submodule placeholder if in a course)
	private function showEntry (&$entry /* Passed by reference, as $entry['_children'] is modified below */, $level = 1, $showInPageLink, /* private */ $nextHeadingHasPageBreak)
	{
		# Start the HTML
		$html = '';
		
		# Handle structural elements
		$type = $entry['type'];		// Convenient alias
		if (!$this->types[$type]['table']) {
			if ($type == 'container') {
				$html .= "\n<h1> " . $entry['name'] . '</h1>';
			}
		}
		
		# Special handling for titles
		#!# Not a very clean implementation - assigns hard-coded logic outside the registry context
		if ($type == 'title') {
			$html .= "\n<h2" . ($entry['pageBreakBefore'] && $nextHeadingHasPageBreak ? ' class="pagebreak"' : '') . '> ' . $entry['name'] . '</h2>';
		}
		
		# Handle normal entries
		if ($this->types[$type]['table']) {
			
			# Start with the title of the entry
			$html .= "\n" . "<h2" . ($entry['pageBreakBefore'] && $nextHeadingHasPageBreak ? ' class="pagebreak"' : '') . ($showInPageLink ? " id=\"{$entry['moniker']}\"><a class=\"inpagelink noprint\" href=\"#{$entry['moniker']}\">#</a" : '') . '> ' . htmlspecialchars ($entry['name']) . '</h2>';
			
			# Provide a link to editing for Administrators
			if ($entry['_editable']) {
				$html .= "\n<ul class=\"editlink right nobullet noprint\"><li><a href=\"{$this->baseUrl}{$entry['_editUrl']}contents.html\"><img src=\"/images/icons/pencil.png\" alt=\"\" class=\"icon\" /></a></li></ul>";
			}
			
			# Determine if there is any entry for this part of the structure
			$id = $entry['id'];
			if (isSet ($this->entries[$type][$id])) {
				$sectionContent = $this->entries[$type][$id];	// Alias for clarity
				
				# Substitute up children entries for entries containing a module
				if (array_key_exists ('modules', $sectionContent)) {		// Can't use isSet as will be NULL
					if (isSet ($entry['_children'])) {
						$sectionContent['modules']  = "\n" . '<div class="modulessection">';
						$sectionContent['modules'] .= $this->combineAll ($entry['_children'], $level++, $showInPageLink);		// Recurse
						$sectionContent['modules'] .= "\n" . '</div>';
						$entry['_children'] = false;	// De-register
					}
				}
				$html .= $this->processTemplate ($this->types[$type]['template'], $type, $sectionContent, $this->metadata[$id]['_editable'], $editingMode = false);
			} else {
				$html .= "\n<p class=\"warning\">(No details yet)</p>";
			}
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Academic year selection list
	private function academicYearSelectionList ()
	{
		# Create a list
		$academicYearList = $this->academicYearList ();
		$list = array ();
		foreach ($academicYearList as $url => $label) {
			$list[] = "<a href=\"{$url}\">{$label}</a>";
		}
		
		# Compile the HTML
		$html  = "<p>Please select an academic year:</p>";
		$html .= application::htmlUl ($list);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to clone year data
	public function cloneyear ()
	{
		# Start the HTML
		$html = '';
		
		# Get the academic years that have data loaded
		if (!$academicYearsInUse = $this->academicYearsInUse ()) {
			$html .= "<p>There is no data entered yet, so nothing is available to copy.</p>";
			echo $html;
			return false;
		}
		
		# Convert to e.g. array ('2013' => '2013-14', ..)
		$academicYears = array ();
		foreach ($academicYearsInUse as $academicYear) {
			preg_match ('/^([0-9]{4})-([0-9]{2})$/', $academicYear, $matches);
			$startYear = $matches[1];
			$nextAcademicYear = ($startYear + 1) . '-' . substr (($startYear + 2), -2);
			$academicYears[$startYear] = "{$academicYear} (create as new {$nextAcademicYear})";
		}
		
		# Remove years where the following year already exists, to leave only those entries available for cloning (which will always include the last in the list)
		$availableAcademicYears = array ();
		foreach ($academicYears as $startYear => $string) {
			$nextStartYear = ($startYear + 1);
			if (!isSet ($academicYears[$nextStartYear])) {
				$availableAcademicYears[$startYear] = $string;
			}
		}
		
		# Create a form
		$form = new form (array (
			'displayRestrictions' => false,
			'formCompleteText' => false,
		));
		$form->heading ('', 'Please select a year to clone from. Please note this can only be done <strong>once</strong>, and it cannot be done.');
		$form->heading ('', 'Note that an academic year is <strong>only</strong> available for selection if there are no entries in the database yet for the following academic year.');
		$form->select (array (
			'name'		=> 'year',
			'title'		=> 'Year to clone from',
			'values'	=> $availableAcademicYears,
			'required'	=> true,
		));
		$form->input (array (
			'name'		=> 'confirm',
			'title'		=> 'Confirm, by typing in YES',
			'required'	=> true,
			'regexp'	=> '^YES$',
			'discard'	=> true,
		));
		if (!$result = $form->process ()) {
			echo $html;
			return;
		}
		
		# Set the start and new years
		$academicYearCurrent	= $result['year'] . '-' . substr (($result['year'] + 1), -2);
		$academicYearNew		= ($result['year'] + 1) . '-' . substr (($result['year'] + 2), -2);
		
		# Clone the nodes
		if (!$nodeIds = $this->cloneyearNodes ($academicYearCurrent, $academicYearNew)) {
			$html .= "<p>{$this->cross} There was a problem cloning the nodes. Please contact the Webmaster.</p>";
			echo $html;
			return false;
		}
		
		# Clone the entries in each table
		if (!$this->cloneyearEntries ($nodeIds)) {
			$html .= "<p>{$this->cross} There was a problem cloning the entries. Please contact the Webmaster.</p>";
			echo $html;
			return false;
		}
		
		# Confirm success
		$html  = "<p>{$this->tick} The {$academicYearCurrent} academic year's data has been successfully cloned to <a href=\"{$this->baseUrl}/{$academicYearNew}/\">{$academicYearNew}</a>.</p>";
		
		# Lock the current academic year
		$this->databaseConnection->update ($this->settings['database'], 'settings', array ('academicYearEarliestEditable' => $academicYearNew), array ('id' => 1));
		$html .= "<p>{$this->tick} The <a href=\"{$this->baseUrl}/settings.html\">settings</a> have been updated to disable editing for {$academicYearCurrent}.</p>";
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to clone an academic year's set of nodes
	private function cloneyearNodes ($academicYearCurrent, $academicYearNew)
	{
		# Get the current nodes
		$currentNodes = $this->databaseConnection->select ($this->settings['database'], 'nodes', array ('academicYear' => $academicYearCurrent), array (), true, $orderBy = 'id');
		
		# Get the max record ID in the table, so that the next available number can be assigned
		$query = "SELECT MAX(id) AS highest FROM {$this->settings['database']}.nodes;";
		$maxRecordId = $this->databaseConnection->getOneField ($query, 'highest');
		$startAt = $maxRecordId + 1 + 10;	// Gap of 10 to deal with changes while this function is being run
		
		# Determine the new IDs, creating a mapping from old => new
		$nodeIds = array ();
		foreach ($currentNodes as $id => $node) {
			$newId = $startAt++;
			$nodeIds[$id] = $newId;
		}
		
		# Prepare the new dataset
		$inserts = array ();
		foreach ($nodeIds as $oldId => $newId) {
			
			# Make a clone of the current record
			$node = $currentNodes[$oldId];
			
			# Modify the cloned node
			$node['id'] = $newId;								// Explicitly define new ID
			$node['academicYear'] = $academicYearNew;			// Put in new academic year
			$node['parentId'] = $nodeIds[$node['parentId']];	// Look up the new node ID
			$node['status'] = 'Draft';							// Set all entries to draft
			$node['createdAt'] = 'NOW()';						// Reset timestamp
			
			# Add to registry of records to insert
			$inserts[] = $node;
		}
		
		// application::dumpData ($nodeIds);
		// application::dumpData ($inserts);
		
		# Do the inserts
		if (!$this->databaseConnection->insertMany ($this->settings['database'], 'nodes', $inserts)) {return false;}
		
		# Return the mapping of nodes
		return $nodeIds;
	}
	
	
	# Function to clone an academic year's set of data
	private function cloneyearEntries ($nodeIds)
	{
		# Get the current entries data
		$data = $this->getEntries (array_keys ($nodeIds));
		
		# For each type, assemble a clone list
		foreach ($data as $type => $entries) {
			
			# Determine the table
			$table = $this->types[$type]['table'];
			
			# Prepare the new dataset
			$inserts = array ();
			foreach ($entries as $oldId => $currentEntry) {
				
				# Make a clone of the current record
				$entry = $currentEntry;
				
				# Modify the cloned record
				unset ($entry['id']);	// Remove ID (allow auto-increment to set)
				$entry['nodeId'] = $nodeIds[$entry['nodeId']];	// Look up the new node ID
				$entry['savedAt'] = 'NOW()';						// Reset timestamp
				
				# Add to registry of records to insert
				$inserts[] = $entry;
			}
			
			# End if nothing to clone
			if (!$inserts) {continue;}
			
			# Do the inserts
			if (!$this->databaseConnection->insertMany ($this->settings['database'], $table, $inserts)) {return false;}
		}
		
		# Return success
		return true;
	}
	
	
	# Settings page
	public function settings ($dataBindingSettingsOverrides = array ())
	{
		# Determine checkboxes for sections/years visible to students
		$visibleToStudents = array ();
		$i = 0;
		foreach ($this->academicYears as $academicYear) {
			$containers = $this->getContainers ($academicYear);
			foreach ($containers as $containerMoniker => $container) {
				$key = $academicYear . '_' . $containerMoniker;
				$label = $academicYear . ': ' . $container['name'];
				$visibleToStudents[$key] = $label;
				$i++;
			}
			$linebreaks[] = $i;		// Linebreak in checkboxes after each year
		}
		
		# Define dataBinding overrides
		$dataBindingSettingsOverrides = array (
			'attributes' => array (
				'academicYearEarliestEditable' => array ('type' => 'select', 'values' => $this->academicYears),
				'visibleToStudents' => array (
					'type'				=> 'checkboxes',
					'values'			=> $visibleToStudents,
					'linebreaks'		=> $linebreaks,
					'defaultPresplit'	=> true,
					'separator'			=> "\n",
					'output'			=> array ('processing' => 'compiled'),	#!# This shouldn't be necessary when using defaultPresplit and separator
				),
			),
		);
		
		# Run the settings page
		parent::settings ($dataBindingSettingsOverrides);
	}
	
	
	
	/* ---------------------------- */
	/*        CRUD functions        */
	/* ---------------------------- */
	
	
	public function edit ()
	{
		# Start the HTML
		$html = '';
		
		# Require a year
		if (!$this->academicYear) {
			$html .= $this->academicYearSelectionList ();
			echo $html;
			return true;
		}
		
		# Hand over to CRUD editing
		echo $this->crudEditing ();
	}
	
	
	# CRUD editing
	private function crudEditing ()
	{
		# Start the HTML
		$html = '';
		
		# Get the actions, action and ID
		if (!$actionsActionId = $this->getActionsActionId ()) {return false;}
		list ($actions, $action, $id, $linkId) = $actionsActionId;
		
		# Define links, which will only be shown to Editors
		$html .= $this->crudLinks ($actions, $action);
		
		# Display a flash if required
		if (in_array ($action, array ('view', 'list'))) {
			$html .= $this->flashMessage ($linkId);
		}
		
		# Assemble the types list
		$types = array ();
		foreach ($this->types as $type => $attributes) {
			$types[$type] = $attributes['label'];
		}
		
		# Define dataBinding overrides
		$dataBindingParameters = array (
			'int1ToCheckbox' => true,
			'attributes' => array (
				'academicYear' => array ('editable' => false, 'default' => $this->academicYear),
				'name' => array ('size' => 70, ),
				'type' => array ('type' => 'radiobuttons', 'values' => $types),
				'parentId' => array ('values' => hierarchy::asIndentedListing ($this->structure), /* 'editable' => false, */ ),
				'moniker' => array ('regexp' => '^([-0-9a-z]{1,40})$', 'prepend' => $this->baseUrl . '/' . $this->academicYear . '/' . ($this->container ? $this->container . '/' : ''), 'append' => '/', 'size' => 20, 'description' => 'Must be unique'),
				'ordering' => array ('type' => 'number', 'min' => 1, 'max' => 99, 'description' => 'Ordering within the parent item noted above', ),
				'editors' => array (
					'type' => 'select',
					'multiple' => true,
					'expandable' => true,
					'separator' => '|',
					'separatorSurround' => true,
					'defaultPresplit' => true,
					'autocomplete' => $this->settings['usersAutocomplete'],
					'autocompleteOptions' => array ('delay' => 0),
					'output' => array ('processing' => 'compiled'),
					'description' => 'Type a surname or username to get a username;<br />One person per line only;<br />These should be listed in order of responsibility.',
				),
				'status' => array ('type' => 'radiobuttons', 'description' => 'Note: Changing this here will <strong>not</strong> send any notifications', ),
				'currentlyWith' => array ('type' => 'radiobuttons', 'description' => 'Note: Changing this here will <strong>not</strong> send any notifications', ),
			),
		);
		
		# Define the generic function to run and a specific action which can be checked for
		$genericFunction = 'crudEditing' . ucfirst ($action);	// e.g. crudEditingAdd
		$specificFunction = $genericFunction . ucfirst ($this->action);	// e.g. crudEditingListEdit
		
		# Run the action
		$function = (method_exists ($this, $specificFunction) ? $specificFunction : $genericFunction);
		$html .= $this->{$function} ('nodes', $id, $dataBindingParameters, array ());
		
		# Return the HTML
		return $html;
	}
	
	
	# Helper function to assign the actions, action and ID for CRUD editing
	private function getActionsActionId ()
	{
		# End if no action or it is empty
		if (!isSet ($_GET['do']) || !strlen ($_GET['do'])) {
			$this->page404 ();
			return false;
		}
		
		# Determine if there is a moniker (and possibly container) supplied
		$moniker = (isSet ($_GET['moniker']) ? $_GET['moniker'] : NULL);
		$containerMoniker = (isSet ($_GET['container']) ? $_GET['container'] : NULL);
		
		# Look up the actual database ID of the node based on the moniker and any container
		$nodeId = $this->getActualId ($moniker, $containerMoniker, $this->academicYear);
		
		# List available actions
		$monikerSanitised = (!is_null ($moniker) ? htmlspecialchars (urlencode ($moniker)) : NULL);
		$containerSlugSanitised = ($containerMoniker ? htmlspecialchars (urlencode ($containerMoniker)) . '/' : NULL);
		$actions = array (
			'list'		=> "<a title=\"List all items\" href=\"{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/\">" . '<img src="/images/icons/application_view_list.png" alt="" class="icon" /> List all</a>',
			'add'		=> false,
			'view'		=> ($moniker ? "<a title=\"View\" href=\"{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/{$containerSlugSanitised}{$monikerSanitised}/\">" . '<img src="/images/icons/page_white.png" alt="" class="icon" /> View</a>' : false),
			'contents'	=> ($moniker ? "<a title=\"Edit contents\" href=\"{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/{$containerSlugSanitised}{$monikerSanitised}/contents.html\">" . '<img src="/images/icons/page_white_edit.png" alt="" class="icon" /> Edit contents</a>' : false),
			'edit'		=> ($moniker ? "<a title=\"Edit structure\" href=\"{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/{$containerSlugSanitised}{$monikerSanitised}/edit.html\">" . '<img src="/images/icons/cog.png" alt="" class="icon" /> Structure</a>' : false),
			//'clone'	=> ($moniker ? "<a title=\"Make a copy of this item\" href=\"{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/{$containerSlugSanitised}{$monikerSanitised}/clone.html\">" . '<img src="/images/icons/page_copy.png" alt="" class="icon" /> Duplicate</a>' : false),
			'delete'	=> ($moniker ? "<a title=\"Delete this item\" href=\"{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/{$containerSlugSanitised}{$monikerSanitised}/delete.html\">" . '<img src="/images/icons/page_white_delete.png" alt="" class="icon" /> Delete</a>' : false),
		);
		
		# Determine if the user has rights for structural operations
		$structureActions = array ('add', 'edit', 'delete', );	#!# Should specify in the actions registry above
		if (!$this->userIsAdministrator) {
			foreach ($structureActions as $action) {
				unset ($actions[$action]);
			}
		}
		
		# Determine if the user has editing rights
		if ($nodeId) {
			$contentActions = array ('contents', );	#!# Should specify in the actions registry above
			$entry = $this->metadata[$nodeId];
			if (!$entry['_editable']) {
				foreach ($contentActions as $action) {
					unset ($actions[$action]);
				}
			}
		}
		
		# Determine the requested action and ensure it is valid
		$action = $_GET['do'];
		if (!isSet ($actions[$action])) {
			$this->page404 ();
			return false;
		}
		
		
		# End if a moniker was supplied but no item found
		if (!is_null ($moniker)) {
			if ($nodeId === false) {
				$this->page404 ();
				return false;
			}
		}
		
		# Return the values
		return array ($actions, $action, $nodeId, $moniker);
	}
	
	
	# Function to get the URL ID from a supplied moniker (and possibly container) value
	private function getActualId ($moniker, $containerMoniker, $academicYear)
	{
		# End if none
		if (is_null ($moniker)) {return $moniker;}
		
		# Determine the initial conditions
		$conditions = array (
			'moniker' => $moniker,
			'academicYear' => $academicYear,
		);
		
		# If a container moniker is supplied, validate it, then get the nodes under it
		if ($containerMoniker) {
			
			# End if an invalid container
			if (!isSet ($this->containers[$containerMoniker])) {return false;}
			$relevantContainerNodeId = $this->containers[$containerMoniker]['id'];
			
			# Get the descendents
			$descendents = $this->hierarchy->getDescendants ($relevantContainerNodeId);
			$descendentIds = array_keys ($descendents);
			
			# Add a constraint, forcing the to-be-found ID to be in the list of descendents
			$conditions['id'] = $descendentIds;		// i.e. IN(id1,id2,...)
		}
		
		# Get the node
		if (!$node = $this->databaseConnection->selectOne ($this->settings['database'], 'nodes', $conditions)) {return false;}
		
		# Return the ID value
		return $node['id'];
	}
	
	
	# Function to create a set of context-sensitive CRUD links
	private function crudLinks ($actions, $action)
	{
		# Compile the HTML
		$html = application::htmlUl ($actions, 0, 'crudlist tabs', true, false, false, $liClass = true, $action);
		
		# Return the HTML
		return $html;
	}
	
	
	# Listing
	private function crudEditingList ($table, $id)
	{
		# Start the HTML
		$html = '';
		
		# Set the ID to highlight in the listing
		if (isSet ($_COOKIE['highlight'])) {
			if (ctype_digit ($_COOKIE['highlight'])) {	// Validate format
				$id = $_COOKIE['highlight'];
			}
			unset ($_COOKIE['highlight']);
			setcookie ('highlight', NULL, -1);	// Unset the cookie
		}
		
		# If the data is hierarchical, show a hierarchical display instead of a table listing
		if ($this->userIsAdministrator) {
			$html .= "\n<p>Click on [+] to add a new item within that entry:</p>";
		} else {
			$html .= "\n<div class=\"graybox\">";
			$totalEditableAreas = count ($this->userHasEditableSections);
			$html .= "\n\t<p>The " . ($totalEditableAreas == 1 ? '<strong>one section</strong> you can edit is' : "<strong>{$totalEditableAreas} sections</strong> you can edit are") . " shown below in <strong class=\"usereditable\">bold</strong><!-- with a <img src=\"/images/icons/pencil.png\" alt=\"\" class=\"icon\" /> icon-->:</p>";	// #!# Commented out pencil icon for now as IE8 doesn't show this generated content
			$html .= "\n</div>";
		}
		$html .= "\n<div class=\"editable\">";
		$html .= hierarchy::asUl ($this->structure, $this->baseUrl, ($this->userIsAdministrator ? "/edit/{$this->academicYearUrlMoniker}/add.html?parent=%s" : false), ($this->userIsAdministrator ? 'edit.html' : false), false, $id);
		$html .= "\n</div>";
		
		# Return the HTML
		return $html;
	}
	
	
	# Show item
	private function crudEditingView ($table, $id)
	{
		# Start the HTML
		$html = '';
		
		# Get the data for the item or end
		$metadata = $this->metadata[$id];
		
		# Show the record
		$html .= $this->recordTable ($metadata, $metadata['id'], $table, 'metadata graybox');
		
		# Show the entry itself
		$html .= $this->entryPage ($metadata);
		
		# Return the HTML
		return $html;
	}
	
	
	# CRUD helper function to get the data for an item based on an ID in the URL
	private function getMetadataForId ($id, &$html)
	{
		# End if none
		if (!isSet ($this->metadata[$id])) {
			$html .= "\n<p>There is no such item <em>" . htmlspecialchars ($id) . "</em>.</p>";
			return false;
		}
		
		# Return the data
		return $this->metadata[$id];
	}
	
	
	# Helper function to show a record's metadata
	private function recordTable ($metadata, $id, $table, $class = false)
	{
		# Start the HTML
		$html = '';
		
		# Get the headings
		$headings = $this->databaseConnection->getHeadings ($this->settings['database'], $table);
		
		# Add internal field headings
		$headings['_inheritedEditors'] = 'Inherited editors';
		
		# Remove internal database fields
		$hideFields = array ('id', 'createdAt', '_editable', '_class', '_hasEntry', '_directEditors');
		foreach ($metadata as $field => $value) {
			if (in_array ($field, $hideFields)) {
				unset ($metadata[$field]);
			}
		}
		
		# Format the parentId field
		$metadata['parentId'] = $this->metadata[$metadata['parentId']]['name'];
		
		# Format the editableBy field
		$metadata['editors'] = $this->editorsStringToNames ($metadata['editors']);
		$metadata['_inheritedEditors'] = $this->editorsStringToNames ($metadata['_inheritedEditors']);
		
		# Show links to older versions
		$olderVersionsLinks = $this->olderVersionsLinks ($id);
		if ($olderVersionsLinks !== NULL) {
			$metadata['Versions'] = $olderVersionsLinks;
		}
		
		# Compile the HTML
		$html .= "\n<h3>" . htmlspecialchars ($metadata['name']) . '</h3>';
		$html .= application::htmlTableKeyed ($metadata, $headings, false, 'lines compressed', $allowHtml = true);
		
		# Surround with a box if required
		$html = "\n<div class=\"{$class}\">" . $html . '</div>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to construct a list of links to older versions of an entry
	private function olderVersionsLinks ($nodeId)
	{
		# Get the data
		if (!$data = $this->getArchivedRecord ($nodeId)) {return $data;}
		
		# Get the metadata for this node
		$metadata = $this->metadata[$nodeId];
		
		# Convert to a list
		$list = array ();
		$latestVersion = max (array_keys ($data));
		foreach ($data as $version => $record) {
			$nameString = $record['editedBy'];
			$timeString = date ('g:ia, jS F Y', strtotime ($record['savedAt']));
			$isLatestVersion = ($version == $latestVersion);
			$list[] = "<a href=\"{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/" . ($this->container ? $this->container . '/' : '') . "{$metadata['moniker']}/" . ($isLatestVersion ? '' : "version{$version}.html") . "\" title=\"Edited by {$nameString} at {$timeString}\">[{$version}" . ($isLatestVersion ? '&nbsp;-&nbsp;current' : '') . "]</a>";
		}
		
		# Compile the HTML
		$html = implode (' ', $list);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to get older versions of a record (or a list of all versions)
	private function getArchivedRecord ($nodeId, $specificVersion = false)
	{
		# Get the metadata for this node
		$metadata = $this->metadata[$nodeId];
		
		# Check this node type supports versioning
		if (!$table = $this->types[$metadata['type']]['table']) {return NULL;}
		
		# Get the data
		$rawData = $this->databaseConnection->select ($this->settings['database'], $table, array ('nodeId' => $nodeId));
		
		# Reindex from 1
		$data = array ();
		$version = 0;
		foreach ($rawData as $id => $record) {
			$version++;
			$data[$version] = $record;
		}
		
		# If required, return only a specific version
		if ($specificVersion) {
			if (!isSet ($data[$specificVersion])) {return false;}
			return $data[$specificVersion];
		}
		
		# Return the data
		return $data;
	}
	
	
	# Function to get an array of the editors
	private function editorsList ($editableBy)
	{
		# End if none
		if (!$editableBy) {return array ();}
		
		# Convert to an array of tokenstring=>userIds
		$list = application::splitCombinedTokenList ($editableBy, '|');
		
		# Return the list
		return $list;
	}
	
	
	# Function to turn an editors string to names
	private function editorsStringToNames ($editableBy)
	{
		# Lookup the names
		$editors = $this->editorsList ($editableBy);
		
		# End if none
		if (!$editors) {return false;}
		
		# Look up each name
		$userNameCallback = $this->settings['userNameCallback'];
		$list = array ();
		foreach ($editors as $username) {
			$list[$username] = $userNameCallback ($username);	// Client code will apply htmlentities to this
		}
		
		# Return the names, comma-separated
		return implode (', ', $list);
	}
	
	
	# Page content editing
	private function crudEditingContents ($table, $id, $dataBindingParameters)
	{
		# Get the metadata for the item or end
		if (!$metadata = $this->getMetadataForId ($id, $html)) {return $html;}
		
		# Show the entry itself
		$html .= $this->entryPage ($metadata, $editable = true);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to assemble an entry
	public function entryPage ($metadata, $editingMode = false)
	{
		# Start the HTML
		$html = '';
		
		# In editing mode, if the entry is finalised, prevent editing by ordinary users
		if ($editingMode) {
			if ($metadata['status'] == 'Finalised') {
				if (!$this->userIsAdministrator) {
					$html = "<p>The entry has now been marked as finalised, so is no longer editable. Please <a href=\"{$this->baseUrl}/feedback.html\">contact the Administrator</a> if a change is necessary.</p>";
					return $html;
				}
			}
		}
		
		# Create alias variables for clarity; this application uses the insert-only versioning pattern
		$type = $metadata['type'];
		$table = $this->types[$type]['table'];
		
		# End if type is purely a container/structural node
		if (!$this->types[$type]['table']) {
			$html .= $this->types[$type]['template'];
			return $html;
		}
		
		# Look up the page contents, selecting the latest record
		$data = $this->databaseConnection->selectOne ($this->settings['database'], $table, array ('nodeId' => $metadata['id']), array (), false, $orderBy = 'savedAt DESC', $limit = 1);
		
		# Obtain the template
		$template = $this->types[$type]['template'];
		
		# Heading
		$html .= "\n<h2>" . htmlspecialchars ($metadata['name']) . '</h2>';
		
		# If not in editing mode, show the record
		if (!$editingMode) {
			
			# If no data, link to the editable area
			$entryBaseUrl = "{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/" . ($this->container ? $this->container . '/' : '') . "{$metadata['moniker']}";
			if (!$data) {
				$html .= "\n<p>This details for this section have not yet been added.</p>";
				#!# Migrate to using _editUrl
				$html .= "\n<p><a class=\"actions\" href=\"{$entryBaseUrl}/contents.html\"><strong><img src=\"/images/icons/pencil.png\" alt=\"\" class=\"icon\" /> Create content for this section</strong></a></p>";
				return $html;
			}
			
			# Get the versions
			$versions = $this->getArchivedRecord ($metadata['id']);
			
			# Determine the version number of the current page
			$latestVersion = max (array_keys ($versions));
			$archivedVersion = $latestVersion;	// Default
			if (isSet ($_GET['version'])) {
				if (!array_key_exists ($_GET['version'], $versions)) {
					$html = "\n<p>There is no such version.</p>";	// #!# Ideally would be page404 but that will appear incorrectly
					return $html;
				}
				$archivedVersion = $_GET['version'];
				
				# Overwrite the contents data
				$data = $versions[$archivedVersion];
			}
			
			# Determine if there is a version to compare from
			$fromVersion = false;
			$dataFrom = false;
			if (isSet ($_GET['fromVersion'])) {
				if (!array_key_exists ($_GET['fromVersion'], $versions)) {
					$html = "\n<p>There is no such archive version.</p>";	// #!# Ideally would be page404 but that will appear incorrectly
					return $html;
				}
				$fromVersion = $_GET['fromVersion'];
				$dataFrom = $versions[$fromVersion];
			}
			
			# Add a comparison control
			$html .= $this->versionComparisonControl ($versions, $fromVersion, $archivedVersion, $entryBaseUrl);
			
			# Add an edit button, if the user has edit rights
			if ($metadata['_editable']) {
				$html .= "\n<p class=\"inpageeditingbutton\"><a class=\"actions\" href=\"{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/" . ($this->container ? $this->container . '/' : '') . "{$metadata['moniker']}/contents.html\"><strong><img src=\"/images/icons/pencil.png\" alt=\"\" class=\"icon\" /> Edit contents</strong>" . ($archivedVersion ? ' (from latest)' : '') . "</a></p>";
			}
			
			# Process template headings
			$html .= $this->processTemplate ($template, $type, $data, $metadata['_editable'], $editingMode, $dataFrom);
			
			# Return the HTML
			return $html;
		}
		
		# If no data, feed the template with an empty record based on the headings
		#!# This is slightly hacky
		if (!$data) {
			$data = array ();
			foreach ($this->types[$type]['fields'] as $field => $attributes) {
				$data[$field] = NULL;
			}
		}
		
		# Show an editing help box
		$html .= $this->editingHelpBox ();
		
		# Assemble the template placeholders
		$template = $this->processTemplate ($template, $type, $data, $metadata['_editable'], $editingMode);
		
		# Substitute the 'modules' placeholder in the papers table template, which is not intended as a real field
		$template = str_replace ('{modules}', '<div class="modulessection"><p>[The course modules will be inserted here.]</p></div>', $template);
		
		# Define the internal fields to exclude from editing
		$exclude = array ('id', 'nodeId', 'editedBy', 'savedAt', 'modules');
		
		# Start HTML for the status control box
		$statusControlHtml  = '';
		
		# Administrators can change between draft and finalised
		$canSetFinalisation = $this->canSetFinalisation ();
		if ($canSetFinalisation) {
			$statusControlHtml .= "\n\t<h3>Status:</h3>";
			$statusControlHtml .= "\n\t<p>{_status}</p>";
		}
		
		# If control (i.e. editability) is with admin, only admin can edit - otherwise anyone in tree
		$adminHasControl = ($metadata['currentlyWith'] == 'Administrators');
		if ($adminHasControl) {
			if (!$this->userIsAdministrator) {
				$html = "\n<p>Control of this entry is currently with the administrator, so you cannot currently edit it.</p>";
				return $html;
			}
		}
		
		# Determine whether control (i.e. editability) is settable by the current user; NB only the person below administrator can pass control to administrator
		$canSetCurrentlyWith = $this->canSetCurrentlyWith ($metadata);
		if ($canSetCurrentlyWith) {
			$statusControlHtml .= "\n\t<h3>Set control (currently {$metadata['currentlyWith']}) to:</h3>";
			$statusControlHtml .= "\n\t<p>{_currentlyWith}</p>";
			# Hide the contact form by default, showing only when the radiobutton is the changed-from-current status
			$currentUserType = ($this->userIsAdministrator ? 'Administrators' : 'Editors');
			$statusControlHtml .= "
				<script type=\"text/javascript\">
					$(document).ready(function(){
						$('#emailcomment')." . ($metadata['currentlyWith'] == $currentUserType ? 'hide' : 'show') . "();
						$(\"input:radio[name='form[_currentlyWith]']\").click(function() {
							if($(this).val() == '" . $currentUserType . "') {
								$('#emailcomment').hide();
							} else {
								$('#emailcomment').show();
							}
						});
					});
				</script>
			";
			$statusControlHtml .= "\n\t<p id=\"emailcomment\">Comments (will be sent by e-mail to the recipient<em></em>) - (optional):";
			$statusControlHtml .= "<br />{_comment}</p>";
		}
		
		# Contributor gets box to message co-ordinator IF (there is a co-ordinator AND status is draft) [checkbox] Notify co-ordinator with message: ____
		$canSendMessageToCoordinator = false;
		if ($metadata['status'] == 'Draft') {
			if ($metadata['_inheritedEditors']) {
				if (in_array ($this->user, $metadata['_directEditors'])) {
					$canSendMessageToCoordinator = true;
					$statusControlHtml .= "\n\t<h3>Message to co-ordinator (optional)</h3>";
					$statusControlHtml .= "\n\t<p>{_messageToCoordinator}</p>";
				}
			}
		}
		
		# Load and instantiate the form library
		$form = new form (array (
			'databaseConnection' => $this->databaseConnection,
			'display' => 'template',
			'displayTemplate' => '<p>{[[PROBLEMS]]}</p>' . $template . "\n<div id=\"status\">" . $statusControlHtml . "\n\n\t<p>{[[SUBMIT]]}</p>\n</div>",
			'unsavedDataProtection' => true,
			//'autofocus' => true,	// #!# Disabled until bug fixed where dataBinding is internally set after manual fields, meaning manual fields at end of form get autofocus
			'jQuery' => false,		// Already loaded on the page
			'richtextEditorAreaCSS' => $this->settings['richtextEditorAreaCSS'],
			'richtextEditorConfig.bodyClass' => $this->settings['richtextEditorConfig.bodyClass'],
			'richtextEditorToolbarSet' => 'BasicLongerFormat',
			'richtextWidth' => 800,
			'richtextHeight' => 250,
		));
		
		# Assemble the dataBinding parameters: add to the supplied parameters
		$dataBindingParameters = array (
			'database' => $this->settings['database'],
			'table' => $table,
			'intelligence' => true,
			'data' => $data,
			'size' => 100,
			'exclude' => $exclude,
			'attributes' => array (
				// pages:
				'pageRichtext' => array ('height' => '400px', ),
				// courses:
			),
		);
		
		# Add status controls
		if ($canSetFinalisation) {
			$form->radiobuttons (array (
				'name'		=> '_status',
				'values'	=> array ('Draft', 'Finalised'),
				'title'		=> 'Status:',
				'required'	=> true,
				'default'	=> ($this->userIsAdministrator ? $metadata['status'] : false),	// Force non-admins to choose every time
			));
		}
		if ($canSetCurrentlyWith) {
			if ($this->userIsAdministrator) {
				$values = array (	// If currently with an admin, it is most likely that Administrators is the usual choice, so list this first
					'Administrators'	=> 'Administrators',
					'Editors'			=> 'Editors',
				);
			} else {
				$values = array (	// If currently with an Editor, show the choices in order of progression
					'Editors'			=> 'Not ready for Administrators yet',
					'Administrators'	=> 'Entry is ready - send to Administrators',
				);
			}
			$form->radiobuttons (array (
				'name'		=> '_currentlyWith',
				'title'		=> "Set control (currently {$metadata['currentlyWith']}) to:",
				'values'	=> $values,
				'default'	=> ($this->userIsAdministrator ? $metadata['currentlyWith'] : false),	// Admins: default to current; Non-admins: force to choose every time
				'required'	=> true,
			));
			$form->textarea (array (
			    'name'		=> '_comment',
			    'title'		=> 'Comment (optional)',
				'rows'		=> 2,
				'cols'		=> 60,
			));
		}
		if ($canSendMessageToCoordinator) {
			$form->textarea (array (
			    'name'		=> '_messageToCoordinator',
			    'title'		=> 'Message to co-ordinator (optional)',
				'rows'		=> 2,
				'cols'		=> 60,
			));
		}
		
		# Create the form widgets, data-binded against the database structure
		$form->dataBinding ($dataBindingParameters);
		
		# Set constraint
		if ($canSetFinalisation && $canSetCurrentlyWith) {
			if ($unfinalisedData = $form->getUnfinalisedData ()) {
				if ($unfinalisedData['_status'] && $unfinalisedData['_currentlyWith']) {
					
					# If the status is Finalised, then the entry must be set to Administrators
					if ($unfinalisedData['_status'] == 'Finalised' && $unfinalisedData['_currentlyWith'] != 'Administrators') {
						$form->registerProblem ('mismatch', 'You cannot set an entry as finalised but pass it back down to Editors.');
					}
				}
			}
		}
		
		# Process the form
		if (!$result = $form->process ($html)) {
			return $html;
		}
		
		# Set any metadata fields
		$updateMetadata = array ();
		if ($canSetFinalisation) {
			$updateMetadata['status'] = $result['_status'];
		}
		if ($canSetCurrentlyWith) {
			$updateMetadata['currentlyWith'] = $result['_currentlyWith'];
			
			# Send a notification if the status has changed or a comment has been added
			$statusChanged = ($result['_currentlyWith'] != $metadata['currentlyWith']);
			$hasComment = ($result['_comment']);
			$sendNotification = ($statusChanged || $hasComment);
			if ($sendNotification) {
				
				# Determine the recipients, if any, and status text
				if ($result['_currentlyWith'] == 'Administrators') {		// i.e. being set to this value
					$recipients = $this->getAdministratorsReceivingEmail (false);
					$status  = 'It is ready for review.';	// i.e. new status will be administrators
				} else {
					$recipients = ($metadata['_inheritedEditors'] ? $metadata['_inheritedEditors'] : $metadata['_directEditors']);	// Favour sending to co-ordinators if present
					$status  = 'It has been reopened for editing.';
				}
				
				# Send the e-mail
				$this->sendNotification ($recipients, $metadata['name'], $status, $result['_comment']);
			}
		}
		
		# Co-ordinator messaging
		if ($canSendMessageToCoordinator) {
			if ($result['_messageToCoordinator']) {
				$this->sendNotification ($metadata['_inheritedEditors'], $metadata['name'], false, $result['_messageToCoordinator']);
			}
		}
		
		# Update the metadata if required
		if ($updateMetadata) {
			$this->databaseConnection->update ($this->settings['database'], 'nodes', $updateMetadata, array ('id' => $metadata['id']));
		}
		
		# Remove any metadata control fields that were attached and posted
		foreach ($result as $key => $value) {
			if (preg_match ('/^_.+/', $key)) {
				unset ($result[$key]);
			}
		}
		
		# Add fixed fields
		$result['nodeId'] = $metadata['id'];
		$result['editedBy'] = $this->user;
		$result['savedAt'] = 'NOW()';
		
		# Save the record; this is always an insert as this application uses the insert-only versioning pattern
		$this->databaseConnection->insert ($this->settings['database'], $table, $result);
		
		# Confirm and redirect (with a flash) to the view page
		$do = ($editingMode ? 'contents' : false);
		$html = $this->flashMessage ($metadata['moniker'], $do);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create a version comparison control
	private function versionComparisonControl ($versions, $fromVersion, $displayedVersion, $entryBaseUrl)
	{
		# End if on first (or only) version, as there is nothing earlier to compare against
		if ($displayedVersion == 1) {return false;}
		
		# Start a list of values
		$values = array ();
		$selected = false;
		$unicodeArrow = chr(0xe2).chr(0x86).chr(0x92);	// See http://www.alanwood.net/unicode/arrows.html
		foreach ($versions as $version => $content) {
			
			# If on the current version, end, showing blank text and a shortened URL
			if ($version == $displayedVersion) {		// End on current
				$url = $entryBaseUrl . '/';
				$values[$url] = '';	// Blank out text
				
				# Select it if no other entry selected so far
				if (!$selected) {
					$selected = $url;
				}
				break;
			}
			
			# Otherwise show an entry to compare with the earlier version
			$url = $entryBaseUrl . "/version{$displayedVersion}from{$version}.html";
			$values[$url] = "Version {$version} ({$versions[$version]['editedBy']}) {$unicodeArrow} {$displayedVersion} ({$versions[$displayedVersion]['editedBy']})";
			if ($version == $fromVersion) {
				$selected = $url;
			}
		}
		
		# Determine if currently viewing a non-current (older) version
		$latestVersion = max (array_keys ($versions));
		$isOlderVersion = ($displayedVersion != $latestVersion);
		
		# Compile the HTML and register a jumplist processor
		$html = application::htmlJumplist ($values, $selected, $_SERVER['_PAGE_URL'], $name = 'version', $parentTabLevel = 0, $class = 'jumplist right', ($isOlderVersion ? "Compare this <span class=\"warning\"><strong>v{$displayedVersion}</strong></span> from:" : 'Compare from:'));
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to determine whether the current user can set finalisation
	private function canSetFinalisation ()
	{
		# Only admins can set finalisation
		return ($this->userIsAdministrator);
	}
	
	
	# Function to determine whether control (i.e. editability) is settable by the current user; NB only the person below administrator can pass control to administrator
	private function canSetCurrentlyWith ($metadata)
	{
		# Admins have rights
		if ($this->userIsAdministrator) {return true;}
		
		# If control is not with admins, and the user is one level below the administrator, they can set
		$adminHasControl = ($metadata['currentlyWith'] == 'Administrators');
		$userIsOneLevelBelowAdministrator = $this->userIsOneLevelBelowAdministrator ($metadata);	// Determine if the user has a right one level below the administrator (i.e. is an inherited editor for this entry, or there are none, is a direct editor for this entry)
		if (!$adminHasControl && $userIsOneLevelBelowAdministrator) {return true;}
		
		# No such right
		return false;
	}
	
	
	# Function to send a notification e-mail
	private function sendNotification ($recipients, $name, $status, $comment)
	{
		# End if no recipients
		if (!$recipients) {return;}
		
		# Convert each recipient from a username to e-mail if required
		foreach ($recipients as $index => $recipient) {
			if (!substr_count ($recipient, '@')) {
				$recipients[$index] = $recipient . '@' . $this->settings['emailDomain'];
			}
		}
		
		# If the current user makes a change which would trigger a notification, that notification should never be e-mailed to themself
		$currentUserEmail = $this->user . '@' . $this->settings['emailDomain'];
		if (in_array ($currentUserEmail, $recipients)) {
			$recipients = array_diff ($recipients, array ($currentUserEmail));
		}
		
		# End if now no recipients
		if (!$recipients) {return;}
		
		# Construct the message text
		$message  = "\n" . ($this->userName ? "{$this->userName} <{$this->user}>" : $this->user) . " has updated the course guide entry: \"{$name}\" at:";
		$message .= "\n\n" . $_SERVER['_PAGE_URL'];		// Note that this correctly takes them to the contents editing page, as that is the one that includes the button
		if ($status) {
			$message .= "\n\n" . $status;
		}
		if ($comment) {		// if comment entered
			$message .= "\n\n" . 'They added the following message:';
			$message .= "\n\n" . $comment;
		}
		
		# Create the headers
		$to = implode (', ', $recipients);
		$subject = "Course guide entry: {$name}";
		$extraHeaders  = "From: {$this->settings['administratorEmail']}";
		$extraHeaders .= "\r\nReply-To: {$this->user}" . '@' . $this->settings['emailDomain'];
		
		# Send the e-mail
		application::utf8Mail ($to, $subject, wordwrap ($message), $extraHeaders);
	}
	
	
	# Function to determine if the user is one level below administrator
	private function userIsOneLevelBelowAdministrator ($metadata)
	{
		# If there are inherited editors, see if the user is one of them
		if ($metadata['_inheritedEditors']) {
			return (in_array ($this->user, $metadata['_inheritedEditors']));
		}
		
		# Otherwise return whether the user is a direct editor
		return (in_array ($this->user, $metadata['_directEditors']));
	}
	
	
	# Function to provide an editing help box
	private function editingHelpBox ()
	{
		# Start the HTML
		$html = '';
		
		# Construct the HTML
		$html .= "\n" . '<div class="metadata graybox">';
		$html .= "\n<h3>Editing tips for text areas</h3>";
		$html .= "\n<ul class=\"noindent spaced\">";
		$html .= "\n\t<li>The text boxes work like a Microsoft Word editing area.</li>";
		$html .= "\n\t<li>Clear all formatting by highlighting the text then pressing the eraser button.</li>";
		$html .= "\n\t<li>Numbered lists: set the start number by right-clicking, then select 'Numbered list properties'.</li>";
		$html .= "\n\t<li>If you have to leave an entry empty, put a single dash -</li>";
		$html .= "\n\t<li>Don't forget to press Submit when finished!</li>";
		$html .= "\n</ul>";
		$html .= "\n" . '</div>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to process the template headings and blocks
	public function processTemplate ($template, $type, $data, $currentUserHasEntryEditingRights, $editingMode, $compareWith = false)
	{
		/*
			$template is a single template string, looking like this:
			
			{heading:coordinator}
			{coordinator}
			{heading:contributor}
			{contributor}
			{heading:mainRichtext}
			{mainRichtext}
			{heading:lecturesRichtext}
			{lecturesRichtext}
			{heading:readingsRichtext}
			{readingsRichtext}
			
			
			$data is the strings/HTML for each part of the entry, looking like this:
			
			array (
				'coordinator' => 'Dr Foo Bar',
				'contributor' => 'Dr Bar Foo',
				'mainRichtext' => '<p>This set of lectures is about blah.</p><p>It covers A, B, C</p>,
				'lecturesRichtext' => <p>Lecture 1 is about foo. Lecture 2 develops the argument of Bar.</p>,
				...
			)
			
		*/
		
		# When viewing, remove entries containing only a dash (-)
		if (!$editingMode) {
			foreach ($data as $field => $value) {
				$textOnlyVersion = trim (strip_tags ($value));
				if ($textOnlyVersion == self::EMPTY_TEXT) {
					$data[$field] = NULL;
				}
			}
		}
		
		# Load diff support if required
		if ($compareWith) {
			require_once ('vendor/brownbear/php-html-diff/src/PhpHtmlDiff/lib/html_diff.php');
		}
		
		# Assemble the template placeholder headings
		$placeholders = array ();
		foreach ($data as $field => $value) {
			
			# Obtain the database field attributes
			$attributes = $this->types[$type]['fields'][$field];
			
			# Determine if the section is visible to staff only
			$visibleToStaffOnly = (isSet ($attributes['_visibleToStaffOnly']) && $attributes['_visibleToStaffOnly']);
			
			# Determine if the section is visible to staff only
			$visibleToEntryEditorsOnly = (isSet ($attributes['_visibleToEntryEditorsOnly']) && $attributes['_visibleToEntryEditorsOnly']);
			
			# Determine if the section is an optional field
			$isOptional = ($attributes['Null'] == 'YES');
			if ($field == 'modules') {$isOptional = false;}		// 'modules' (present in the papers table only) is basically just a marker to make in-loop insertion easier; users never enter text for it
			
			# In view mode, do not show optional headings whose value is empty
			$viewModeIsOptionalEmpty = (!$editingMode && $isOptional && !strlen ($value));
			
			# Skip the 'Text of page' heading
			if ($field == 'pageRichtext') {$viewModeIsOptionalEmpty = true;}
			
			# Skip empty text entries in view mode
			if (!$editingMode && ($value === NULL)) {$viewModeIsOptionalEmpty = true;}
			
			# Assemble the heading
			$placeholderHeading = '{heading:' . $field . '}';
			$placeholders[$placeholderHeading] = ($viewModeIsOptionalEmpty ? '' : '<h3>' . htmlspecialchars ($attributes['Comment']) . ($isOptional && $editingMode ? ' &nbsp; <em>(optional)</em>' : '') . ($visibleToStaffOnly ? ' &nbsp; <em>(Private: visible only to staff)</em>' : '') . ($visibleToEntryEditorsOnly ? ' &nbsp; <em>(Private: visible only to entry editors)</em>' : '') . '</h3>');
			
			# If viewing, process the values also
			if (!$editingMode) {
				$placeholderValue = '{' . $field . '}';
				$placeholders[$placeholderValue] = ($compareWith ? html_diff ($value, $compareWith[$field]) : $value);
			}
			
			# When viewing, if the field is hidden, for staff show the section faded out, and for non-staff, remove the heading and value entirely
			if (!$editingMode) {
				if ($visibleToStaffOnly) {
					if ($this->userIsStaff) {
						$placeholders[$placeholderHeading] = "\n<div class=\"staffonly\">" . $placeholders[$placeholderHeading];
						$placeholders[$placeholderValue] = $placeholders[$placeholderValue] . "\n</div>";
					} else {
						$placeholders[$placeholderHeading] = '';
						$placeholders[$placeholderValue] = '';
					}
				}
			}
			
			# For entries visible only to editors of the entry, show only if the current user has rights for that entry
			if (!$editingMode) {
				if ($visibleToEntryEditorsOnly) {
					if ($currentUserHasEntryEditingRights) {
						$placeholders[$placeholderHeading] = "\n<div class=\"editoronly\">" . $placeholders[$placeholderHeading];
						$placeholders[$placeholderValue] = $placeholders[$placeholderValue] . "\n</div>";
					} else {
						$placeholders[$placeholderHeading] = '';
						$placeholders[$placeholderValue] = '';
					}
				}
			}
		}
		
		# Substitute the placeholders in the template
		$html = strtr ($template, $placeholders);
		
		# Return the processed template
		return $html;
	}
	
	
	public function crudEditingAddEdit ($table, $id, $dataBindingParameters)
	{
		# Start the HTML
		$html = '';
		
		# Ensure a parentId is supplied
		if (!isSet ($_GET['parent'])) {
			$html = "\n<p>No parent entry was specified in the URL. Please go back and try again.</p>";
			return $html;
		}
		
		# If a supplied ID is empty or not numeric, throw a 404
		if (isSet ($_GET['parent']) && (!strlen ($_GET['parent']) || !ctype_digit ($_GET['parent']))) {
			$html = "\n<p>The supplied ID (<em>" . htmlspecialchars ($_GET['parent']) . "</em>) was not correct. Please check the URL and try again.</p>";
			return $html;
		}
		
		# Start with no parent ID supplied
		$parentId = false;
		
		# If a parent ID (whose syntax is already confirmed correct) is specified, require selection
		if (isSet ($_GET['parent'])) {
			$parentId = $_GET['parent'];
			
			# Ensure the node exists
			if (!$node = $this->hierarchy->nodeExists ($parentId)) {
				$html = "\n<p>The supplied ID (<em>" . htmlspecialchars ($_GET['parent']) . "</em>) does not exist.</p>";
				$parentId = false;
				return $html;
			}
		}
		
		# Get the children of this node
		$children = $this->hierarchy->childrenOf ($parentId, "{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/add.html?parent=%s");
		
		# Determine the next ordering number
		$query = "SELECT (IFNULL(MAX(ordering), 0) + 1) AS ordering FROM {$this->settings['database']}.{$table} WHERE parentId = {$parentId};";
		$ordering = $this->databaseConnection->getOneField ($query, 'ordering');
		$dataBindingParameters['attributes']['ordering']['default'] = $ordering;
		
		# Set the likely child type if set
		if (isSet ($this->types[$node['type']]['likelyChild'])) {
			$dataBindingParameters['attributes']['type']['default'] = $this->types[$node['type']]['likelyChild'];
		}
		
		# Add additional dataBinding overrides
		$dataBindingParameters['attributes']['parentId']['default'] = $parentId;
		$dataBindingParameters['attributes']['parentId']['editable'] = false;
		
		# Add the parentId's container, if any
		if ($relevantContainerNodeId = $this->hierarchy->getNearestAncestorHavingAttributeValue ($parentId, 'type', 'container', false, true)) {
			$containerMoniker = $this->metadata[$relevantContainerNodeId]['moniker'];
			$dataBindingParameters['attributes']['moniker']['prepend'] .= $containerMoniker . '/';
		}
		
		# Add the edit form
		$html = $this->crudEditingAdd ($table, $id, $dataBindingParameters);
		
		# Return the HTML
		return $html;
	}
	
	
	# Addition
	private function crudEditingAdd ($table, $id, $dataBindingParameters, $fixedData = array (), $cloneMode = false)
	{
		# Start the HTML
		$html = '';
		
		# In clone mode, obtain the data for the item being cloned, but strip out its ID
		$data = array ();
		if ($cloneMode) {
			if (!$data = $this->getMetadataForId ($id, $html)) {return $html;}
			unset ($data['id']);
		}
		
		# Show the form or end
		if (!$result = $this->dataForm ($html, $table, $dataBindingParameters, $fixedData, $data, $additionalResults, $cloneMode)) {return $html;}
		
		# Insert the record
		$this->databaseConnection->insert ($this->settings['database'], $table, $result);
		
		# Set a short-life cookie for highlighting the last-updated value, which will be used on the landing redirect page
		$lastInsertId = $this->databaseConnection->getLatestId ();
		setcookie ('highlight', $lastInsertId, time () + 60, $cookiePath);
		
		# Confirm and redirect (with a flash) to the view page
		$html = $this->flashMessage ($result['moniker'], 'add');
		
		# Return the HTML
		return $html;
	}
	
	
	# Editing
	private function crudEditingEdit ($table, $id, $dataBindingParameters, $fixedData)
	{
		# Start the HTML
		$html = '';
		
		# Get the data for the item or end
		if (!$data = $this->getMetadataForId ($id, $html)) {return $html;}
		
		# Add the current monikers in the current node context as a constraint for editing the form moniker field
		$dataBindingParameters['attributes']['moniker']['current'] = $this->getExistingMonikers ($data);
		
		# Show the form or end
		if (!$result = $this->dataForm ($html, $table, $dataBindingParameters, $fixedData, $data, $additionalResults)) {return $html;}
		
		# Insert the record
		#!# Would be useful if database::update() $conditions could default to array(id=>$id) if just $id supplied rather than an array
		$id = $data['id'];
		$conditions = array ('id' => $id);
		$this->databaseConnection->update ($this->settings['database'], $table, $result, $conditions);
		
		# Determine the link ID
		$linkId = (isSet ($result['moniker']) ? $result['moniker'] : $id);
		
		# Confirm and redirect (with a flash) to the view page
		$html .= $this->flashMessage ($linkId, 'edit');
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to get the current monikers from within the current node context; e.g. if within part1a, get other monikers under part1a; or if nearer the root node of the hierarchy, get all monikers
	private function getExistingMonikers ($node)
	{
		# Get the nearest container's nodeID
		$relevantContainerNodeId = $this->hierarchy->getNearestAncestorHavingAttributeValue ($node['id'], 'type', 'container', true);
		
		# Get the items under the relevant container node
		$descendents = $this->hierarchy->getDescendants ($relevantContainerNodeId);
		
		# Get the monikers
		$monikers = array ();
		foreach ($descendents as $nodeId => $descendent) {
			$monikers[$nodeId] = $descendent['moniker'];
		}
		
		# Remove the current moniker, to avoid forms excluding the current value
		$currentMoniker = $node['moniker'];
		$monikers = array_diff ($monikers, array ($currentMoniker));
		
		# Return the list
		return $monikers;
	}
	
	
	# Function to set a flash message
	private function flashMessage ($linkId, $do = false, $additionalCookies = array ())
	{
		# Start the HTML
		$html = '';
		
		$linkId = htmlspecialchars ($linkId);	// urlencode seems not to be needed, as the list is not part of a query string
		
		# Set the description
		$actions = array (
			'add'		=> array ('description' => 'Thanks; the structure has been successfully created',	'redirection' => ''),
			'contents'	=> array ('description' => 'Thanks; the contents have been successfully saved',		'redirection' => "{$linkId}/"),
			'edit'		=> array ('description' => 'Thanks; the structure has been successfully updated',	'redirection' => "{$linkId}/"),
		);
		
		# Determine the redirection location
		if ($do) {
			$redirectTo = "{$this->baseUrl}/edit/{$this->academicYearUrlMoniker}/" . ($this->container ? $this->container . '/' : '') . "{$actions[$do]['redirection']}";	// When adding/editing, redirect back to the main listing
		}
		
		# Set a redirection message
		$recordDescription = 'record ' . $linkId;
		if ($do) {$recordDescription = "<a href=\"{$redirectTo}\">{$recordDescription}</a>";}
		$message = "\n<p class=\"flashmessage\">{$this->tick}<strong> %s.</strong></p>";
		$message = "\n<div class=\"graybox\">" . $message . "\n</div>";
		
		# If an achieved action has been set, set the flash
		$cookiePath = $this->baseUrl . '/';
		if ($do) {
			
			# Set a flash and redirect
			$html = application::setFlashMessage ($this->action, $do, $redirectTo, sprintf ($message, $actions[$do]['description']), $cookiePath);
			
		# Otherwise, retrieve the message
		} else {
			if ($do = application::getFlashMessage ($this->action, $cookiePath)) {
				if (isSet ($actions[$do]['description'])) {
					$html = sprintf ($message, $actions[$do]['description']);
				}
			}
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Cloning; basically the same as addition but with data pre-filled
	private function crudEditingClone ($table, $id, $dataBindingParameters, $fixedData)
	{
		# Same as addition
		$html = $this->crudEditingAdd ($table, $id, $dataBindingParameters, $fixedData, $cloneMode = true);
		
		# Return the HTML
		return $html;
	}
	
	
	# Deletion
	private function crudEditingDelete ($table, $id)
	{
		# Start the HTML
		$html = '';
		
		# Get the data for the item
		$data = $this->metadata[$id];
		
		# Create the deletion form
		$formHtml = '';
		$form = new form (array (
			'displayRestrictions' => false,
			'formCompleteText' => false,
			'displayColons' => false,
		));
		$options = array ();
		$options['record'] = 'Yes, delete record #' . $data['id'];
		$widgetType = 'checkboxes';
		$form->{$widgetType} (array (
			'name'			=> 'confirm',
			'title'			=> 'Do you really want to delete the record below?',
			'values'		=> $options,
			'required' 		=> 1,
			'entities'		=> false,
		));
		$result = $form->process ($formHtml);
		
		# Assemble the HTML for the form and the record
		$html .= $formHtml;
		$html .= $this->recordTable ($data, $data['id'], $table, 'graybox');
		
		# Show the HTML
		if (!$result) {return $html;}
		
		# Delete the record/series
		$conditions = array ('id' => $data['id']);	// The checkbox or a radiobutton must have been selected to get this far
		$this->databaseConnection->delete ($this->settings['database'], $table, $conditions);
		
		# Confirm and link back
		$html = "\n<p>Thanks; the record has now been deleted.</p>";
		
		# Return the HTML
		return $html;
	}
	
	
	# Data form
	private function dataForm (&$html, $table, $dataBindingParameters, $fixedData, $data, &$additionalResults = array (), $cloneMode = false)
	{
		# Load and instantiate the form library
		$form = new form (array (
			'nullText' => '',
			'databaseConnection' => $this->databaseConnection,
			'displayRestrictions' => false,
			'unsavedDataProtection' => true,
			'picker' => true,
			'autofocus' => (!$data),
			'jQuery' => false,		// Already loaded on the page
		));
		
		# Assemble the dataBinding parameters: add to the supplied parameters
		$dataBindingParameters = $dataBindingParameters /* these supplied parameters take priority */ + array (
			'database' => $this->settings['database'],
			'table' => $table,
			'intelligence' => true,
			'simpleJoin' => true,
			'data' => $data,
			'exclude' => array_keys ($fixedData),
			'truncate' => 60,	// Needed to stop areaOfActivity entries being truncated
			'editingUniquenessUniChecking' => (!$cloneMode),	// Disable the UNI checking if cloning
		);
		
		# Create the form widgets, data-binded against the database structure
		$form->dataBinding ($dataBindingParameters);
		
		# Process the form
		$result = $form->process ($html);
		
		# Inject fixed data, which will have been excluded from form widget creation
		#!# Question of whether this should apply only to edit or add/edit (e.g. case of creator vs last-edited-by)
		if ($result) {
			if ($fixedData) {
				foreach ($fixedData as $key => $value) {
					$result[$key] = $value;
				}
			}
		}
		
		# Return the result
		return $result;
	}
}

?>
