<?php
define('PLUGIN_VERSION', '1.1.0'); //Declaring the plugin version
define('PLUGIN_SLUG', 'wordpress-per-product-payments/wordpress-per-product-payments.php'); //Declaring the slug of the main plugin file

$github_username = 'Sandman2-0';                    //Github user from who the update will be aquired
$github_repo = 'Wordpress-Per-Product-Payments';    //The Github repository for this plugin
$asset_name = 'wordpress-per-product-payments.zip'; //The name of the asset Wordpress pulls from a release


function check_for_plugin_update() { //Function that checks for plugin updates
    global $github_username, $github_repo, $asset_name;
    $api_url = "https://api.github.com/repos/{$github_username}/{$github_repo}/releases/latest"; //Definging the Github latest release API url

    $response = wp_remote_get($api_url, array('timeout' => 10)); //Initiate request to the Github API to retrieve the release info
    if (is_wp_error($response)) {
		error_log('GitHub API Error: ' . $response->get_error_message()); //Logging any API errors
        return false;
    }

    $release = json_decode(wp_remote_retrieve_body($response)); //Decode the JSON response
	if (!$release) {
        error_log('Failed to decode GitHub response body.'); //If the release variable is empty, log as a decode error
        return false;
    }
	error_log('GitHub Release Data: ' . print_r($release, true)); //Here for debugging purposes (prints the aquired release data into the error log. remove if not needed)
	
	$latest_version = ltrim($release->tag_name, 'v');            //Removing the "v" prefix from release version if it's present in the tag
    if (version_compare(PLUGIN_VERSION, $latest_version, '<')) { //Checking if the plugin version is below the latest release version
        $package_url = '';                                       //Setting the package URL variable
        foreach ($release->assets as $asset) {                   //Loop through the release assets
            if ($asset->name === $asset_name) {                  //Check if an asset within the release matches the provided asset name
                $package_url = $asset->browser_download_url;     //If satisfied, get the download URL to the asset and store it within the package_url variable
                break; //End the loop once this process is done.
            }
        }

        if (!$package_url) { //Check if the package URL was not located
			error_log('Package URL not found in GitHub assets.'); //Log this as an error
            return false;
        }
        else { //If a package URL is successfully retrieved, 
			error_log('Selected Package URL: ' . $package_url); //Here for debugging purposes (prints the aquired package URL. remove if not needed)
			
            $plugin_data = array(       //Compile the plugin update data
                'slug' => PLUGIN_SLUG,              //The stated plugin slug
                'new_version' => $latest_version,   //The fetched latest version
                'url' => $release->html_url,        //The URL to the release
                'package' => $package_url           //The aquired asset URL
            );
			
            return $plugin_data; //Returning the newly created data array. This is used by Wordpress to handle the update
			
        }
	
    }
	else { //If the current plugin version isn't below the latest release version, print that there is no update available
		error_log('No update available. Current version: ' . PLUGIN_VERSION . ', Latest version: ' . $release->tag_name);
	}
}

function get_plugin_changelog_url() { //Function to get the plugin changelog file from Github. Will be displayed within the update details of the plugin
    global $github_username, $github_repo;
    $api_url = "https://api.github.com/repos/{$github_username}/{$github_repo}/contents/changelogs.txt"; //Defining the GitHub API URL for the changelogs

    
    $response = wp_remote_get($api_url, array('timeout' => 10)); //Fetching the changelog content from GitHub
    if (is_wp_error($response)) {
        error_log('GitHub API Error: ' . $response->get_error_message()); //Log any API errors when they occur
        return false;
    }

    $changelog = json_decode(wp_remote_retrieve_body($response)); //Decode the JSON response
    if ($changelog && isset($changelog->content)) {               //Check if the pulled response contains content
        return base64_decode($changelog->content);                //Return the decoded base64 response
    } 
    else {
        error_log('Changelog file is empty or not found in the specified GitHub repository.'); //If no content is received, print this in the error log
        return false;
    }
}

function integrate_with_wordpress_update($transient) { //Integrating the plugin with the WordPress update mechanism
    if (empty($transient->checked)) { //If a plugin check has not been executed by Wordpress, stop any further processes.
        return $transient;
    }

    $update_data = check_for_plugin_update(); //Update_data variable stores the results of the plugin update check
    if ($update_data) {                          //If there is available update data, retrieve the changelog content
        $changelog = get_plugin_changelog_url();
        if ($changelog) {                        //If the changelog content is successfully retrieved, add the changelog to the update data
            $update_data['sections'] = array('changelog' => nl2br($changelog),);
            error_log('Update Data: ' . print_r($update_data, true)); //Here for debugging purposes (prints the new data array into the error log. remove if not needed)
        } 
        else {
            error_log('Failed to retrieve changelog for update.'); //If the changelog variable is empty, print a retrieval error
        }

        $transient->response[PLUGIN_SLUG] = (object) $update_data; //Adding the plugin update data to the transient response for Wordpress to initiate an update
    }

    return $transient; //Returning the modified transient response
}

function plugin_update_info($false, $action, $response) { //Function to provide the changelog info within the plugin update details
    if (isset($response->slug) && $response->slug === PLUGIN_SLUG) { //Check if the response slug matches the plugin slug
        $changelog = get_plugin_changelog_url(); //Get the changelog content
        if ($changelog) {                        //If the changelog is successfully acquired, 
            $response->sections = array('changelog' => nl2br($changelog),); //Add the changelog information to the detail response while being formatted for HTML display
        } 
        else {
            error_log('Failed to retrieve changelog for update details.'); //Add to error log if the changelog retrieval fails
        }
    }

    return $response; //Return the modified response
}

add_filter('pre_set_site_transient_update_plugins', 'integrate_with_wordpress_update'); //Filter for plugin update checks
add_filter('plugins_api', 'plugin_update_info', 10, 3); //Filter for the plugins API
?>