<?php
/**
 * @wordpress-plugin
 * Plugin Name: Header Media Controls
 * Plugin URI:  https://github.com/neverything/header-media-controls
 * Description: Add additional controls to header media.
 * Version:     1.0.0
 * Author:      Silvan Hagen
 * Author URI:  https://silvanhagen.com/
 * License:     GPLv2+
 * GitHub Plugin URI: neverything/header-media-controls
 *
 * @package Neverything\HeaderMediaControls
 */

namespace Neverything\HeaderMediaControls;

/**
 * Add hooks to get the plugin running.
 */
function add_inline_script() {
	/**
	 * Bail early if video isn't active.
	 */
	if ( ! is_header_video_active() ) {
		return;
	}

	/**
	 * Add the dashicons on the front-end for all users.
	 * Note: So far not all dashicons are available as svg by default.
	 */
	wp_enqueue_style( 'dashicons' );

	/**
	 * Makes the custom audio control button look nice.
	 */
	wp_add_inline_style( 'dashicons',
		'#wp-custom-header-video-button-audio {
				right: 80px;
			}
			#wp-custom-header-video-button-audio span.dashicons {
				width: 24px;
				height: 24px;
				font-size: 24px;
			}'
	);

	/**
	 * Add audio control to video headers and allow the video to not play as loop.
	 */
	wp_add_inline_script( 'wp-custom-header',
		"(function() {
			window.onload = function() {
				var button = document.createElement( 'button' ),
				container = document.getElementById( 'wp-custom-header' ); 
				button.setAttribute( 'type', 'button' );
				button.setAttribute( 'id', 'wp-custom-header-video-button-audio' );
				if ( HeaderMediaControls.unmute_on_load == 1 ) {
					button.setAttribute( 'class', 'wp-custom-header-video-button wp-custom-header-video-mute' );
					button.innerHTML = HeaderMediaControls.mute;
				} else {
					button.setAttribute( 'class', 'wp-custom-header-video-button wp-custom-header-video-unmute' );
					button.innerHTML = HeaderMediaControls.unmute;
				}
				
                if ( window.YT ) {
                    if ( HeaderMediaControls.unmute_on_load == 1 ) {
                        wp.customHeader.handlers.youtube.player.unMute();
                    }

					if ( HeaderMediaControls.dont_loop ) {
						wp.customHeader.handlers.youtube.player.addEventListener( 'onStateChange', function(e) {
							if ( YT.PlayerState.ENDED === e.data ) {
								e.target.pauseVideo();
							}
						});
					}
					button.addEventListener( 'click', function(e) {
	                    if ( wp.customHeader.handlers.youtube.player.isMuted() ) {
	                        wp.customHeader.handlers.youtube.player.unMute()
	                        button.setAttribute( 'class', 'wp-custom-header-video-button wp-custom-header-video-mute' );
							button.innerHTML = HeaderMediaControls.mute;
	                    } else {
	                        wp.customHeader.handlers.youtube.player.mute()
	                        button.setAttribute( 'class', 'wp-custom-header-video-button wp-custom-header-video-unmute' );
							button.innerHTML = HeaderMediaControls.unmute;
	                    }
	                });
					container.appendChild(button);
				} else if ( typeof wp.customHeader.handlers.nativeVideo.video != 'undefined' ) {
					if ( HeaderMediaControls.unmute_on_load == 1 ) {
                        wp.customHeader.handlers.nativeVideo.video.muted=false;
                    }
					if ( HeaderMediaControls.dont_loop ) {
	                    wp.customHeader.handlers.nativeVideo.video.loop=false;
	                }
	                button.addEventListener( 'click', function(e) {
	                    if ( wp.customHeader.handlers.nativeVideo.video.muted ) {
	                        wp.customHeader.handlers.nativeVideo.video.muted=false;
	                        button.setAttribute( 'class', 'wp-custom-header-video-button wp-custom-header-video-mute' );
							button.innerHTML = HeaderMediaControls.mute;
	                    } else {
	                        wp.customHeader.handlers.nativeVideo.video.muted=true;
	                        button.setAttribute( 'class', 'wp-custom-header-video-button wp-custom-header-video-unmute' );
							button.innerHTML = HeaderMediaControls.unmute;
	                    }
	                });
	                container.appendChild(button);
	            }
			}
		})();"
	);

	/**
	 * Add defaults for header media control options.
	 */
	$header_media_controls = wp_parse_args( get_option( 'header_media_controls' ), [
		'dont_loop'      => 0,
		'unmute_on_load' => 0,
	] );

	/**
	 * Setup localization for the audio button.
	 */
	$localize_args = [
		'mute'   => '<span class="screen-reader-text">' . __( 'Mute', 'header-media-controls' ) . '</span><span class="dashicons dashicons-controls-volumeoff"></span>',
		'unmute' => '<span class="screen-reader-text">' . __( 'Unmute', 'header-media-controls' ) . '</span><span class="dashicons dashicons-controls-volumeon"></span>',
	];

	/**
	 * Localize wp-custom-header scripts with our data.
	 */
	wp_localize_script(
		'wp-custom-header',
		'HeaderMediaControls',
		array_merge( $localize_args, $header_media_controls )
	);
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\add_inline_script' );

/**
 * Add customizer controls for header media.
 *
 * @param \WP_Customize_Manager $wp_customize current customizer manager.
 */
function add_customize_control( $wp_customize ) {

	/**
	 * Add setting for looping the video to the customizer.
	 */
	$wp_customize->add_setting( 'header_media_controls[dont_loop]', [
		'capability' => 'edit_theme_options',
		'type'       => 'option',
	] );

	/**
	 * Checkbox in the customizer header media section to stop videos from looping.
	 */
	$wp_customize->add_control( 'dont_loop_video', [
		'settings' => 'header_media_controls[dont_loop]',
		'label'    => __( 'Disable Video Loop', 'header-media-controls' ),
		'section'  => 'header_image',
		'type'     => 'checkbox',
	] );

	/**
	 * Add setting for audio on/off to the customizer.
	 */
	$wp_customize->add_setting( 'header_media_controls[unmute_on_load]', [
		'capability' => 'edit_theme_options',
		'type'       => 'option',
	] );

	/**
	 * Checkbox in the customizer header media section to unmute videos on load.
	 */
	$wp_customize->add_control( 'unmute_video', [
		'settings' => 'header_media_controls[unmute_on_load]',
		'label'    => __( 'Autoplay Audio (very annoying)', 'header-media-controls' ),
		'section'  => 'header_image',
		'type'     => 'checkbox',
	] );
}

add_action( 'customize_register', __NAMESPACE__ . '\add_customize_control' );
