<?php
namespace WpAssetCleanUp;

/**
 *
 * Class Overview
 * @package WpAssetCleanUp
 */
class Overview
{
	/**
	 * @var array
	 */
	public $data = array();

	/**
	 *
	 */
	public function pageOverview()
	{
		global $wpdb;

		$wpacuPluginId = WPACU_PLUGIN_ID;

		$allHandles = array();

		/*
		 * Per page rules (unload, load exceptions if a bulk rule is enabled, async & defer for SCRIPT tags)
		 */
		// Homepage (Unloads)
		$wpacuFrontPageUnloads = get_option(WPACU_PLUGIN_ID . '_front_page_no_load');

		if ($wpacuFrontPageUnloads) {
			$wpacuFrontPageUnloadsArray = @json_decode( $wpacuFrontPageUnloads, ARRAY_A );

			foreach (array('styles', 'scripts') as $assetType) {
				if ( isset( $wpacuFrontPageUnloadsArray[$assetType] ) && ! empty( $wpacuFrontPageUnloadsArray[$assetType] ) ) {
					foreach ( $wpacuFrontPageUnloadsArray[$assetType] as $assetHandle ) {
						$allHandles[$assetType][ $assetHandle ]['unload_on_home_page'] = 1;
					}
				}
			}
		}

		// Homepage (Load Exceptions)
		$wpacuFrontPageLoadExceptions = get_option(WPACU_PLUGIN_ID . '_front_page_load_exceptions');

		if ($wpacuFrontPageLoadExceptions) {
			$wpacuFrontPageLoadExceptionsArray = @json_decode( $wpacuFrontPageLoadExceptions, ARRAY_A );

			foreach ( array('styles', 'scripts') as $assetType ) {
				if ( isset( $wpacuFrontPageLoadExceptionsArray[$assetType] ) && ! empty( $wpacuFrontPageLoadExceptionsArray[$assetType] ) ) {
					foreach ( $wpacuFrontPageLoadExceptionsArray[$assetType] as $assetHandle ) {
						$allHandles[$assetType][ $assetHandle ]['load_exception_on_home_page'] = 1;
					}
				}
			}
		}

		// Homepage (async, defer)
		$wpacuFrontPageData = get_option(WPACU_PLUGIN_ID . '_front_page_data');

		if ($wpacuFrontPageData) {
			$wpacuFrontPageDataArray = @json_decode( $wpacuFrontPageData, ARRAY_A );
			if ( isset($wpacuFrontPageDataArray['scripts']) && ! empty($wpacuFrontPageDataArray['scripts']) ) {
                foreach ($wpacuFrontPageDataArray['scripts'] as $assetHandle => $assetData) {
                    if (isset($assetData['attributes']) && ! empty($assetData['attributes'])) {
	                    // async, defer attributes
	                    $allHandles['scripts'][ $assetHandle ]['script_attrs']['home_page'] = $assetData['attributes'];
                    }
                }
            }

            // Do not apply "async", "defer" exceptions (e.g. "defer" is applied site-wide, except the home page)
			if (isset($wpacuFrontPageDataArray['scripts_attributes_no_load']) && ! empty($wpacuFrontPageDataArray['scripts_attributes_no_load'])) {
			    foreach ($wpacuFrontPageDataArray['scripts_attributes_no_load'] as $assetHandle => $assetAttrsNoLoad) {
				    $allHandles['scripts'][$assetHandle]['attrs_no_load']['home_page'] = $assetAttrsNoLoad;
			    }
			}
		}

		// Get all Asset CleanUp (Pro) meta keys from all WordPress meta tables where it can be possibly used
		foreach (array($wpdb->postmeta, $wpdb->termmeta, $wpdb->usermeta) as $tableName) {
			$wpacuGetValuesQuery = <<<SQL
SELECT * FROM `{$tableName}`
WHERE meta_key IN('_{$wpacuPluginId}_no_load', '_{$wpacuPluginId}_data', '_{$wpacuPluginId}_load_exceptions')
SQL;
			$wpacuMetaData = $wpdb->get_results( $wpacuGetValuesQuery, ARRAY_A );

			foreach ( $wpacuMetaData as $wpacuValues ) {
				$decodedValues = @json_decode( $wpacuValues['meta_value'], ARRAY_A );

				if ( empty( $decodedValues ) ) {
					continue;
				}

				// $refId is the ID for the targeted element from the meta table which could be: post, taxonomy ID or user ID
				if ($tableName === $wpdb->postmeta) {
					$refId = $wpacuValues['post_id'];
					$refKey = 'post';
				} elseif ($tableName === $wpdb->termmeta) {
					$refId = $wpacuValues['term_id'];
					$refKey = 'term';
				} else {
					$refId = $wpacuValues['user_id'];
					$refKey = 'user';
				}

				if ( $wpacuValues['meta_key'] === '_' . $wpacuPluginId . '_no_load' ) {
					foreach ( $decodedValues as $assetType => $assetHandles ) {
						foreach ( $assetHandles as $assetHandle ) {
							// Unload it on this page
							$allHandles[ $assetType ][ $assetHandle ]['unload_on_this_page'][$refKey][] = $refId;
						}
					}
				} elseif ( $wpacuValues['meta_key'] === '_' . $wpacuPluginId . '_load_exceptions' ) {
					foreach ( $decodedValues as $assetType => $assetHandles ) {
						foreach ( $assetHandles as $assetHandle ) {
							// If bulk unloaded, 'Load it on this page'
							$allHandles[ $assetType ][ $assetHandle ]['load_exception_on_this_page'][$refKey][] = $refId;
						}
					}
				} elseif ( $wpacuValues['meta_key'] === '_' . $wpacuPluginId . '_data' ) {
					if ( isset( $decodedValues['scripts'] ) && ! empty( $decodedValues['scripts'] ) ) {
						foreach ( $decodedValues['scripts'] as $scriptHandle => $scriptData ) {
							if ( isset( $scriptData['attributes'] ) && ! empty( $scriptData['attributes'] ) ) {
								// async, defer attributes
								$allHandles['scripts'][ $scriptHandle ]['script_attrs'][$refKey][$refId] = $scriptData['attributes'];
							}
						}
					}

					if ( isset( $decodedValues['scripts_attributes_no_load'] ) && ! empty( $decodedValues['scripts_attributes_no_load'] ) ) {
						foreach ( $decodedValues['scripts_attributes_no_load'] as $scriptHandle => $scriptNoLoadAttrs ) {
					        $allHandles['scripts'][$scriptHandle]['attrs_no_load'][$refKey][$refId] = $scriptNoLoadAttrs;
						}
                    }
				}
			}
		}

		/*
		 * Global (Site-wide) Rules: Preloading, Position changing, Unload via RegEx, etc.
		 */
		$wpacuGlobalData = get_option(WPACU_PLUGIN_ID . '_global_data');
		$wpacuGlobalDataArray = @json_decode($wpacuGlobalData, ARRAY_A);

		foreach (array('styles', 'scripts') as $assetType) {
			foreach (array('preloads', 'positions', 'notes', 'ignore_child', 'everywhere', 'date', '404', 'search') as $dataType) {
				if ( isset( $wpacuGlobalDataArray[ $assetType ][$dataType] ) && ! empty( $wpacuGlobalDataArray[ $assetType ][$dataType] ) ) {
					foreach ( $wpacuGlobalDataArray[ $assetType ][$dataType] as $assetHandle => $dataValue ) {
						if ($dataType === 'everywhere' && $assetType === 'scripts' && isset($dataValue['attributes'])) {
							if (count($dataValue['attributes']) === 0) {
								continue;
							}
							// async/defer applied site-wide
							$allHandles[ $assetType ][ $assetHandle ]['script_site_wide_attrs'] = $dataValue['attributes'];
						} elseif ($dataType !== 'everywhere' && $assetType === 'scripts' && isset($dataValue['attributes'])) {
						    // For date, 404, search pages
							$allHandles[ $assetType ][ $assetHandle ]['script_attrs'][$dataType] = $dataValue['attributes'];
						} else {
							$allHandles[ $assetType ][ $assetHandle ][ $dataType ] = $dataValue;
						}
					}
				}
			}

			foreach (array('unload_regex', 'load_regex') as $unloadType) {
				if (isset($wpacuGlobalDataArray[$assetType][$unloadType]) && ! empty($wpacuGlobalDataArray[$assetType][$unloadType])) {
					foreach ($wpacuGlobalDataArray[$assetType][$unloadType] as $assetHandle => $unloadData) {
						if (isset($unloadData['enable'], $unloadData['value']) && $unloadData['enable'] && $unloadData['value']) {
							$allHandles[ $assetType ][ $assetHandle ][$unloadType] = $unloadData['value'];
						}
					}
				}
			}
		}

		// Do not apply "async", "defer" exceptions (e.g. "defer" is applied site-wide, except the 404, search, date)
		if (isset($wpacuGlobalDataArray['scripts_attributes_no_load']) && ! empty($wpacuGlobalDataArray['scripts_attributes_no_load'])) {
			foreach ($wpacuGlobalDataArray['scripts_attributes_no_load'] as $unloadedIn => $unloadedInValues) {
			    foreach ($unloadedInValues as $assetHandle => $assetAttrsNoLoad) {
				    $allHandles['scripts'][$assetHandle]['attrs_no_load'][$unloadedIn] = $assetAttrsNoLoad;
			    }
			}
		}

		/*
		 * Unload Site-Wide (Everywhere) Rules: Preloading, Position changing, Unload via RegEx, etc.
		 */
		$wpacuGlobalUnloadData = get_option(WPACU_PLUGIN_ID . '_global_unload');
		$wpacuGlobalUnloadDataArray = @json_decode($wpacuGlobalUnloadData, ARRAY_A);

		foreach (array('styles', 'scripts') as $assetType) {
			if (isset($wpacuGlobalUnloadDataArray[$assetType]) && ! empty($wpacuGlobalUnloadDataArray[$assetType])) {
				foreach ($wpacuGlobalUnloadDataArray[$assetType] as $assetHandle) {
					$allHandles[ $assetType ][ $assetHandle ]['unload_site_wide'] = 1;
				}
			}
		}

		/*
		* Bulk Unload Rules - post, page, custom post type (e.g. product, download), taxonomy (e.g. category), 404, date, etc.
		*/
		$wpacuBulkUnloadData = get_option(WPACU_PLUGIN_ID . '_bulk_unload');
		$wpacuBulkUnloadDataArray = @json_decode($wpacuBulkUnloadData, ARRAY_A);

		foreach (array('styles', 'scripts') as $assetType) {
			if (isset($wpacuBulkUnloadDataArray[$assetType]) && ! empty($wpacuBulkUnloadDataArray[$assetType])) {
				foreach ($wpacuBulkUnloadDataArray[$assetType] as $unloadBulkType => $unloadBulkValues) {
					if (empty($unloadBulkValues)) {
						continue;
					}

					// $unloadBulkType could be 'post_type', 'date', '404', 'taxonomy', 'search'
					if ($unloadBulkType === 'post_type') {
						foreach ($unloadBulkValues as $postType => $assetHandles) {
							foreach ($assetHandles as $assetHandle) {
								$allHandles[ $assetType ][ $assetHandle ]['unload_bulk']['post_type'][] = $postType;
							}
						}
					} elseif (in_array($unloadBulkType, array('date', '404', 'search'))) {
						foreach ($unloadBulkValues as $assetHandle) {
							$allHandles[ $assetType ][ $assetHandle ]['unload_bulk'][$unloadBulkType] = 1;
						}
					} elseif ($unloadBulkType === 'taxonomy') {
						foreach ($unloadBulkValues as $taxonomyType => $assetHandles) {
							foreach ($assetHandles as $assetHandle) {
								$allHandles[ $assetType ][ $assetHandle ]['unload_bulk']['taxonomy'][] = $taxonomyType;
							}
						}
					} elseif ($unloadBulkType === 'author' && isset($unloadBulkValues['all']) && ! empty($unloadBulkValues['all'])) {
						foreach ($unloadBulkValues['all'] as $assetHandle) {
							$allHandles[ $assetType ][ $assetHandle ]['unload_bulk']['author'] = 1;
						}
					}
				}
			}
		}

		if (isset($allHandles['styles'])) {
			ksort($allHandles['styles']);
		}

		if (isset($allHandles['scripts'])) {
			ksort($allHandles['scripts']);
		}

		$this->data['handles'] = $allHandles;

		if (isset($this->data['handles']['styles']) || isset($this->data['handles']['scripts'])) {
			// Only fetch the assets information if there is something to be shown
			// to avoid useless queries to the database
			$this->data['assets_info'] = Main::getHandlesInfo();
			}

		Main::instance()->parseTemplate('admin-page-overview', $this->data, true);
	}

	/**
	 * @param $handle
	 * @param $assetType
	 * @param $data
	 * @param string $for ('default': bulk unloads, regex unloads)
	 */
	public static function renderHandleTd($handle, $assetType, $data, $for = 'default')
	{
		global $wp_version;

		$handleData = '';
		$isCoreFile = false; // default

        if (isset($data['handles'][$assetType][$handle]) && $data['handles'][$assetType][$handle]) {
            $handleData = $data['handles'][$assetType][$handle];
        }

        if ( $for === 'default' ) {
            $src = (isset( $data['assets_info'][ $assetType ][ $handle ]['src'] ) && $data['assets_info'][ $assetType ][ $handle ]['src']) ? $data['assets_info'][ $assetType ][ $handle ]['src'] : false;

            $isExternalSrc = true;

            if (\WpAssetCleanUp\Misc::getLocalSrc($src)
                || strpos($src, '/?') !== false // Dynamic Local URL
                || strpos(str_replace(site_url(), '', $src), '?') === 0 // Starts with ? right after the site url (it's a local URL)
            ) {
                $isExternalSrc = false;
                $isCoreFile = Misc::isCoreFile($data['assets_info'][$assetType][$handle]);
            }

            if (strpos($src, '/') === 0 && strpos($src, '//') !== 0) {
                $src = site_url() . $src;
            }

	        $ver = $wp_version; // default
	        if (isset($data['assets_info'][ $assetType ][ $handle ]['ver'] ) && $data['assets_info'][ $assetType ][ $handle ]['ver'] ) {
		        $ver = is_array($data['assets_info'][ $assetType ][ $handle ]['ver'] )
			        ? implode(',', $data['assets_info'][ $assetType ][ $handle ]['ver'] )
			        : $data['assets_info'][ $assetType ][ $handle ]['ver'] ;
	        }
	        ?>
            <strong><span style="color: green;"><?php echo $handle; ?></span></strong>
            <small><em>v<?php echo $ver; ?></em></small>
            <?php
            if ($isCoreFile) {
                ?>
                <span title="WordPress Core File" style="font-size: 15px; vertical-align: middle;" class="dashicons dashicons-wordpress-alt wpacu-tooltip"></span>
                <?php
            }
            ?>
            <?php
            // [wpacu_pro]
            // If called from "Bulk Changes" -> "Preloads"
            $preloadedStatus = isset($data['assets_info'][ $assetType ][ $handle ]['preloaded_status']) ? $data['assets_info'][ $assetType ][ $handle ]['preloaded_status'] : false;
            if ($preloadedStatus === 'async') { echo '&nbsp;(<strong><em>'.$preloadedStatus.'</em></strong>)'; }
            // [/wpacu_pro]

            $handleExtras = array();

            // If called from "Overview"
	        if (isset($handleData['preloads']) && $handleData['preloads']) {
		        $handleExtras[0] = '<span style="font-weight: 600;">Preloaded</span>';

	            if ($handleData['preloads'] === 'async') {
		            $handleExtras[0] .= ' (async)';
                }
	        }

	        if (isset($handleData['positions']) && $handleData['positions']) {
                $handleExtras[1] = '<span style="color: #004567; font-weight: 600;">Moved to <code>&lt;'.$handleData['positions'].'&gt;</code></span>';
            }

	        /*
	         * 1) Per page (homepage, a post, a category, etc.)
	         * Async, Defer attributes
	         */
            // Per home page
	        if (isset($handleData['script_attrs']['home_page']) && ! empty($handleData['script_attrs']['home_page'])) {
		        ksort($handleData['script_attrs']['home_page']);
		        $handleExtras[2] = 'Homepage attributes: <strong>'.implode(', ', $handleData['script_attrs']['home_page']).'</strong>';
	        }

	        // Date archive pages
	        if (isset($handleData['script_attrs']['date']) && ! empty($handleData['script_attrs']['date'])) {
		        ksort($handleData['script_attrs']['date']);
		        $handleExtras[22] = 'Date archive attributes: <strong>'.implode(', ', $handleData['script_attrs']['date']).'</strong>';
	        }

	        // 404 page
	        if (isset($handleData['script_attrs']['404']) && ! empty($handleData['script_attrs']['404'])) {
		        ksort($handleData['script_attrs']['404']);
		        $handleExtras[23] = '404 Not Found attributes: <strong>'.implode(', ', $handleData['script_attrs']['404']).'</strong>';
	        }

	        // Search results page
	        if (isset($handleData['script_attrs']['search']) && ! empty($handleData['script_attrs']['search'])) {
		        ksort($handleData['script_attrs']['search']);
		        $handleExtras[24] = '404 Not Found attributes: <strong>'.implode(', ', $handleData['script_attrs']['search']).'</strong>';
	        }

	        // Per post page
            if (isset($handleData['script_attrs']['post']) && ! empty($handleData['script_attrs']['post'])) {
	            $handleExtras[3] = 'Per post attributes: ';

		        $postsList = '';

		        ksort($handleData['script_attrs']['post']);

		        foreach ($handleData['script_attrs']['post'] as $postId => $attrList) {
			        $postData   = get_post($postId);
			        $postTitle  = $postData->post_title;
			        $postType   = $postData->post_type;
			        $postsList .= '<a title="Post Title: '.$postTitle.', Post Type: '.$postType.'" class="wpacu-tooltip" target="_blank" href="'.admin_url('post.php?post='.$postId.'&action=edit').'">'.$postId.'</a> - <strong>'.implode(', ', $attrList).'</strong> / ';
		        }

	            $handleExtras[3] .= rtrim($postsList, ' / ');
	        }

            // User archive page (specific author)
	        if (isset($handleData['script_attrs']['user']) && ! empty($handleData['script_attrs']['user'])) {
		        $handleExtras[31] = 'Per author page attributes: ';

		        $authorPagesList = '';

		        ksort($handleData['script_attrs']['user']);

		        foreach ($handleData['script_attrs']['user'] as $userId => $attrList) {
			        $authorLink = get_author_posts_url(get_the_author_meta('ID', $userId));
			        $authorRelLink = str_replace(site_url(), '', $authorLink);

			        $authorPagesList .= '<a target="_blank" href="'.$authorLink.'">'.$authorRelLink.'</a> - <strong>'.implode(', ', $attrList).'</strong> | ';
		        }

		        $authorPagesList = trim($authorPagesList, ' | ');

		        $handleExtras[31] .= rtrim($authorPagesList, ' / ');
	        }

            // Per category page
            if (isset($handleData['script_attrs']['term']) && ! empty($handleData['script_attrs']['term'])) {
	            $handleExtras[33] = 'Per taxonomy attributes: ';

                $taxPagesList = '';

	            foreach ($handleData['script_attrs']['term'] as $termId => $attrList) {
		            $taxData     = get_term( $termId );
		            $taxonomy    = $taxData->taxonomy;
		            $termLink    = get_term_link( $taxData, $taxonomy );
		            $termRelLink = str_replace( site_url(), '', $termLink );

		            $taxPagesList .= '<a href="' . $termRelLink . '">' . $termRelLink . '</a> - <strong>'.implode(', ', $attrList).'</strong> | ';
	            }

	            $taxPagesList = trim($taxPagesList, ' | ');

	            $handleExtras[33] .= rtrim($taxPagesList, ' / ');
            }

            /*
             * 2) Site-wide type
             * Any async, defer site-wide attributes? Exceptions will be also shown
             */
	        if (isset($handleData['script_site_wide_attrs'])) {
		        $handleExtras[4] = 'Site-wide attributes: ';
		        foreach ( $handleData['script_site_wide_attrs'] as $attrValue ) {
			        $handleExtras[4] .= '<strong>' . $attrValue . '</strong>, ';

			        // Are there any exceptions? e.g. async, defer unloaded site-wide, but loaded on the homepage
			        if ( isset( $handleData['attrs_no_load'] ) && ! empty( $handleData['attrs_no_load'] ) ) {
				        // $attrSetIn could be 'home_page', 'term', 'user', 'date', '404', 'search'
				        $handleExtras[4] .= ' <em>(with exceptions from applying added for these pages: ';

				        $handleAttrsExceptionsList = '';

				        foreach ( $handleData['attrs_no_load'] as $attrSetIn => $attrSetValues ) {
					        if ( $attrSetIn === 'home_page' && in_array($attrValue, $attrSetValues) ) {
						        $handleAttrsExceptionsList .= ' Homepage, ';
					        }

					        if ( $attrSetIn === 'date' && in_array($attrValue, $attrSetValues) ) {
						        $handleAttrsExceptionsList .= ' Date Archive, ';
					        }

					        if ( $attrSetIn === '404' && in_array($attrValue, $attrSetValues) ) {
						        $handleAttrsExceptionsList .= ' 404 Not Found, ';
					        }

					        if ( $attrSetIn === 'search' && in_array($attrValue, $attrSetValues) ) {
						        $handleAttrsExceptionsList .= ' Search Results, ';
					        }

					        // Post pages such as posts, pages, product (WooCommerce), download (Easy Digital Downloads), etc.
					        if ( $attrSetIn === 'post' ) {
						        $postPagesList = '';

						        foreach ( $attrSetValues as $postId => $attrSetValuesTwo ) {
							        if (! in_array($attrValue, $attrSetValuesTwo)) {
								        continue;
							        }

							        $postData   = get_post($postId);
							        $postTitle  = $postData->post_title;
							        $postType   = $postData->post_type;

							        $postPagesList .= '<a title="Post Title: '.$postTitle.', Post Type: '.$postType.'" class="wpacu-tooltip" target="_blank" href="'.admin_url('post.php?post='.$postId.'&action=edit').'">'.$postId.'</a> | ';
						        }

						        if ($postPagesList) {
						            $postPagesList = trim( $postPagesList, ' | ' ).', ';
						            $handleAttrsExceptionsList .= $postPagesList;
						        }
					        }

					        // Taxonomy pages such as category archive, product category in WooCommerce
					        if ( $attrSetIn === 'term' ) {
						        $taxPagesList = '';

						        foreach ( $attrSetValues as $termId => $attrSetValuesTwo ) {
						            if (! in_array($attrValue, $attrSetValuesTwo)) {
						                continue;
                                    }

							        $taxData     = get_term( $termId );
							        $taxonomy    = $taxData->taxonomy;
							        $termLink    = get_term_link( $taxData, $taxonomy );
							        $termRelLink = str_replace( site_url(), '', $termLink );

							        $taxPagesList .= '<a href="' . $termRelLink . '">' . $termRelLink . '</a> | ';
						        }

						        if ($taxPagesList) {
							        $taxPagesList = trim( $taxPagesList, ' | ' ) . ', ';
							        $handleAttrsExceptionsList .= $taxPagesList;
						        }
					        }

					        // Author archive pages (e.g. /author/john/page/2/)
					        if ($attrSetIn === 'user') {
						        $authorPagesList = '';

						        foreach ( $attrSetValues as $userId => $attrSetValuesTwo ) {
							        if (! in_array($attrValue, $attrSetValuesTwo)) {
								        continue;
							        }

							        $authorLink = get_author_posts_url(get_the_author_meta('ID', $userId));
							        $authorRelLink = str_replace(site_url(), '', $authorLink);

							        $authorPagesList .= '<a target="_blank" href="'.$authorLink.'">'.$authorRelLink.'</a> | ';
						        }

						        if ($authorPagesList) {
						            $authorPagesList = trim( $authorPagesList, ' | ' ).', ';
						            $handleAttrsExceptionsList .= $authorPagesList;
						        }
                            }
				        }

				        $handleAttrsExceptionsList = trim($handleAttrsExceptionsList, ', ');

				        $handleExtras[4] .= $handleAttrsExceptionsList;
				        $handleExtras[4] .= '</em>), ';
			        }
		        }

		        $handleExtras[4] = trim($handleExtras[4], ', ');
	        }

	        if (! empty($handleExtras)) {
		        echo '<small>' . implode( ' <span style="font-weight: 300; color: grey;">/</span> ', $handleExtras ) . '</small>';
	        }

            if ( $src ) {
                $verDb = (isset($data['assets_info'][ $assetType ][ $handle ]['ver']) && $data['assets_info'][ $assetType ][ $handle ]['ver']) ? $data['assets_info'][ $assetType ][ $handle ]['ver'] : false;

		        $appendAfterSrc = (strpos($src, '?') === false) ? '?' : '&';

		        if ( $verDb ) {
		            if (is_array($verDb)) {
			            $appendAfterSrc .= http_build_query(array('ver' => $data['assets_info'][ $assetType ][ $handle ]['ver']));
                    } else {
			            $appendAfterSrc .= 'ver='.$ver;
                    }
		        } else {
			        $appendAfterSrc .= 'ver='.$wp_version; // default
		        }
		        ?>
                <div><a <?php if ($isExternalSrc) { ?> data-wpacu-external-source="<?php echo $src . $appendAfterSrc; ?>" <?php } ?> href="<?php echo $src . $appendAfterSrc; ?>" target="_blank"><small><?php echo str_replace( site_url(), '', $src ); ?></small></a> <?php if ($isExternalSrc) { ?><span data-wpacu-external-source-status></span><?php } ?></div>
            <?php
            }

            // Any note?
            if (isset($handleData['notes']) && $handleData['notes']) {
                ?>
                <div><small><span class="dashicons dashicons-welcome-write-blog"></span> Note: <em><?php echo ucfirst(htmlspecialchars($data['handles'][$assetType][$handle]['notes'])); ?></em></small></div>
                <?php
            }
            ?>
        <?php
        }
	}

	/**
	 * @param $handleData
	 *
	 * @return mixed
	 */
	public static function renderHandleChangesOutput($handleData)
	{
		$handleChangesOutput = array();
		$anyGroupPostUnloadRule = false; // default (turns to true if any unload rule that applies on multiple pages for posts is set)
		$anyLoadExceptionRule = false; // default (turns to true if any load exception rule is set)

		// Site-wide
		if (isset($handleData['unload_site_wide'])) {
			$handleChangesOutput['site_wide'] = '<span style="color: #cc0000;">Unloaded site-wide (everywhere)</span>';
			$anyGroupPostUnloadRule = true;
		}

		// Bulk unload (on all posts, categories, etc.)
		if (isset($handleData['unload_bulk'])) {
			$handleChangesOutput['bulk'] = '';

			if (isset($handleData['unload_bulk']['post_type'])) {
				foreach ($handleData['unload_bulk']['post_type'] as $postType) {
					$handleChangesOutput['bulk'] .= ' Unloaded on all pages of <strong>' . $postType . '</strong> post type, ';
					$anyGroupPostUnloadRule = true;
				}
			}

			if (isset($handleData['unload_bulk']['taxonomy']) && ! empty($handleData['unload_bulk']['taxonomy'])) {
				$handleChangesOutput['bulk'] .= ' Unloaded for all pages belonging to the following taxonomies: <strong>'.implode(', ', $handleData['unload_bulk']['taxonomy']).'</strong>, ';
			}

			if (isset($handleData['unload_bulk']['date']) || isset($handleData['unload_bulk']['404']) || isset($handleData['unload_bulk']['search'])) {
				foreach ($handleData['unload_bulk'] as $bulkType => $bulkValue) {
					if ($bulkType === 'date' && $bulkValue === 1) {
						$handleChangesOutput['bulk'] .= ' Unloaded on all archive `Date` pages (any date), ';
					}
					if ($bulkType === 'search' && $bulkValue === 1) {
						$handleChangesOutput['bulk'] .= ' Unloaded on `Search` page (any keyword), ';
					}
					if ($bulkType === '404' && $bulkValue === 1) {
						$handleChangesOutput['bulk'] .= ' Unloaded on `404 Not Found` page (any URL), ';
					}
				}
			}

			if (isset($handleData['unload_bulk']['author']) && $handleData['unload_bulk']['author']) {
				$handleChangesOutput['bulk'] .= ' Unloaded on all author pages, ';
			}

			$handleChangesOutput['bulk'] = rtrim($handleChangesOutput['bulk'], ', ');

			if (isset($handleChangesOutput['site_wide'])) {
				$handleChangesOutput['bulk'] .= ' * <em>overwritten by the site-wide rule</em>';
			}
		}

		if (isset($handleData['unload_on_home_page']) && $handleData['unload_on_home_page']) {
			$handleChangesOutput['on_home_page'] = '<span style="color: #cc0000;">Unloaded</span> on the <a target="_blank" href="'.Misc::getPageUrl(0).'">homepage</a>';

			if (isset($handleChangesOutput['site_wide'])) {
				$handleChangesOutput['on_home_page'] .= ' * <em>overwritten by the site-wide rule</em>';
			}
        }

		if (isset($handleData['load_exception_on_home_page']) && $handleData['load_exception_on_home_page']) {
			$handleChangesOutput['load_exception_on_home_page'] = '<span style="color: green;">Loaded (as an exception)</span> on the <a target="_blank" href="'.Misc::getPageUrl(0).'">homepage</a>';
		}

		// On this page: post, page, custom post type
		if (isset($handleData['unload_on_this_page']['post'])) {
			$handleChangesOutput['on_this_post'] = 'Unloaded in the following posts: ';

			$postsList = '';

			sort($handleData['unload_on_this_page']['post']);

			foreach ($handleData['unload_on_this_page']['post'] as $postId) {
				$postData   = get_post($postId);
				$postTitle  = $postData->post_title;
				$postType   = $postData->post_type;
				$postsList .= '<a title="Post Title: '.$postTitle.', Post Type: '.$postType.'" class="wpacu-tooltip" target="_blank" href="'.admin_url('post.php?post='.$postId.'&action=edit').'">'.$postId.'</a>, ';
			}

			$handleChangesOutput['on_this_post'] .= rtrim($postsList, ', ');

			if (isset($handleChangesOutput['site_wide'])) {
				$handleChangesOutput['on_this_post'] .= ' * <em>overwritten by the site-wide rule</em>';
            }
		}

		// Unload on this page: taxonomy such as 'category', 'product_cat' (specific one, not all categories)
		if (isset($handleData['unload_on_this_page']['term'])) {
			$handleChangesOutput['on_this_tax'] = '<span style="color: #cc0000;">Unloaded</span> in the following pages: ';

			$taxList = '';

			sort($handleData['unload_on_this_page']['term']);

			foreach ($handleData['unload_on_this_page']['term'] as $termId) {
				$taxData   = get_term($termId);
				$taxonomy = $taxData->taxonomy;
				$termLink = get_term_link($taxData, $taxonomy);
				$termRelLink = str_replace(site_url(), '', $termLink);

				$taxList .= '<a target="_blank" href="'.$termLink.'">'.$termRelLink.'</a>, ';
			}

			$handleChangesOutput['on_this_tax'] .= rtrim($taxList, ', ');

			if (isset($handleChangesOutput['site_wide'])) {
				$handleChangesOutput['on_this_tax'] .= ' * <em>overwritten by the site-wide rule</em>';
			}
		}

		if (isset($handleData['unload_on_this_page']['user'])) {
			$handleChangesOutput['on_this_tax'] = '<span style="color: #cc0000;">Unloaded</span> in the following author pages: ';

			$taxList = '';

			sort($handleData['unload_on_this_page']['user']);

			foreach ($handleData['unload_on_this_page']['user'] as $userId) {
				$authorLink = get_author_posts_url(get_the_author_meta('ID', $userId));
				$authorRelLink = str_replace(site_url(), '', $authorLink);

				$taxList .= '<a target="_blank" href="'.$authorLink.'">'.$authorRelLink.'</a>, ';
			}

			$handleChangesOutput['on_this_tax'] .= rtrim($taxList, ', ');

			if (isset($handleChangesOutput['site_wide'])) {
				$handleChangesOutput['on_this_tax'] .= ' * <em>overwritten by the site-wide rule</em>';
			}
		}

		// Unload via RegEx
		if (isset($handleData['unload_regex']) && $handleData['unload_regex']) {
			$handleChangesOutput['unloaded_via_regex'] = '<span style="color: #cc0000;">Unloads if</span> the request URI (from the URL) matches this RegEx(es): <code>'.nl2br($handleData['unload_regex']).'</code>';

			if (isset($handleChangesOutput['site_wide'])) {
				$handleChangesOutput['unloaded_via_regex'] .= ' * <em>overwritten by the site-wide rule</em>';
			}

			$anyGroupPostUnloadRule = true;
		}

		if (isset($handleData['ignore_child']) && $handleData['ignore_child']) {
            $handleChangesOutput['ignore_child'] = 'If unloaded by any rule, ignore dependencies and keep its "children" loaded';
		}

		// Load exceptions? Per page, via RegEx
		if (isset($handleData['load_exception_on_this_page']['post'])) {
			$handleChangesOutput['load_exception_on_this_post'] = '<span style="color: green;">Loaded (as an exception)</span> in the following posts: ';

			$postsList = '';

			sort($handleData['load_exception_on_this_page']['post']);

			foreach ($handleData['load_exception_on_this_page']['post'] as $postId) {
				$postData   = get_post($postId);
				$postTitle  = $postData->post_title;
				$postType   = $postData->post_type;
				$postsList .= '<a title="Post Title: '.$postTitle.', Post Type: '.$postType.'" class="wpacu-tooltip" target="_blank" href="'.admin_url('post.php?post='.$postId.'&action=edit').'">'.$postId.'</a>, ';
			}

			$handleChangesOutput['load_exception_on_this_post'] .= rtrim($postsList, ', ');
			$anyLoadExceptionRule = true;
		}

		if (isset($handleData['load_regex']) && $handleData['load_regex']) {
		    if (isset($handleChangesOutput['load_exception_on_this_post'])) {
		        $textToShow = ' and also if the request URI (from the URL) matches this RegEx';
            } else {
			    $textToShow = '<span style="color: green;">Loaded (as an exception)</span> if the request URI (from the URL) matches this RegEx(es)';
            }

			$handleChangesOutput['load_exception_regex'] = $textToShow.': <code>'.nl2br($handleData['load_regex']).'</code>';
			$anyLoadExceptionRule = true;
		}

		// Since more than one load exception rule is set, merge them on the same row to save space and avoid duplicated words
		if (isset($handleChangesOutput['load_exception_on_this_post'], $handleChangesOutput['load_exception_regex'])) {
			$handleChangesOutput['load_exception_all'] = $handleChangesOutput['load_exception_on_this_post'] . $handleChangesOutput['load_exception_regex'];
			unset($handleChangesOutput['load_exception_on_this_post'], $handleChangesOutput['load_exception_regex']);
        }

		if (! $anyGroupPostUnloadRule && $anyLoadExceptionRule) {
			$handleChangesOutput['load_exception_notice'] = '<p><em><small><strong>Note:</strong> Although a load exception rule is added, it is not relevant as there are no rules that would work together with it (e.g. unloaded site-wide, on all posts). This exception can be removed as the file is loaded anyway in all pages.</small></em></p>';
		}

		return $handleChangesOutput;
	}
}
