<?php
// Adapted from https://github.com/andrefelipe/vite-php-setup/blob/master/public/helpers.php
namespace CatFolder\Classes;

define( 'IS_DEVELOPMENT', true );

class Vite {
	public static function base_path() {
		return CATF_PLUGIN_URL . 'assets/dist1/';
	}

	public static function enqueueVite( string $script = 'main.tsx' ) {
		self::jsPreloadImports( $script );
		self::cssTag( $script );
		self::register( $script );
		add_filter(
			'script_loader_tag',
			function ( $tag, $handle, $src ) {
				if ( str_contains( $handle, 'module/yay/' ) ) {
					$str  = "type='module'";
					$str .= true ? ' crossorigin' : '';
					// $tag  = str_replace( "type='text/javascript'", $str, $tag );
					$tag = '<script ' . $str . ' src="' . $src . '" id="' . $handle . '-js"></script>';
				}
				return $tag;
			},
			10,
			3
		);
	}

	public static function register( $entry ) {
		$url = IS_DEVELOPMENT
		? 'http://localhost:3000/' . $entry
		: self::assetUrl( $entry );

		if ( ! $url ) {
			return '';
		}

		wp_register_script( "module/yay/$entry", $url, false, true, true );
		wp_enqueue_script( "module/yay/$entry" );
	}

	private static function jsPreloadImports( $entry ) {
		if ( IS_DEVELOPMENT ) {
			add_action(
				'admin_head',
				function () {
					echo '<script type="module">
                    import RefreshRuntime from "http://localhost:3000/@react-refresh"
                    RefreshRuntime.injectIntoGlobalHook(window)
                    window.$RefreshReg$ = () => {}
                    window.$RefreshSig$ = () => (type) => type
                    window.__vite_plugin_react_preamble_installed__ = true
                    </script>';
				}
			);
			return;
		}

		$res = '';
		foreach ( self::importsUrls( $entry ) as $url ) {
			$res .= '<link rel="modulepreload" href="' . $url . '">';
		}

		add_action(
			'admin_head',
			function () use ( &$res ) {
				echo $res;
			}
		);
	}

	private static function cssTag( string $entry ): string {
		// not needed on dev, it's inject by Vite
		if ( IS_DEVELOPMENT ) {
			return '';
		}

		$tags = '';
		foreach ( self::cssUrls( $entry ) as $url ) {
			wp_register_style( "yay/$entry", $url );
			wp_enqueue_style( "yay/$entry", $url );
		}
		return $tags;
	}


	// Helpers to locate files

	private static function getManifest(): array {
		$content = file_get_contents( CATF_PLUGIN_PATH . 'assets/dist1/manifest.json' );

		return json_decode( $content, true );
	}

	private static function assetUrl( string $entry ): string {
		$manifest = self::getManifest();

		return isset( $manifest[ $entry ] )
		? self::base_path() . $manifest[ $entry ]['file']
		: self::base_path() . $entry;
	}

	private static function getPublicURLBase() {
		return IS_DEVELOPMENT ? '/dist/' : self::base_path();
	}

	private static function importsUrls( string $entry ): array {
		$urls     = array();
		$manifest = self::getManifest();

		if ( ! empty( $manifest[ $entry ]['imports'] ) ) {
			foreach ( $manifest[ $entry ]['imports'] as $imports ) {
				$urls[] = self::getPublicURLBase() . $manifest[ $imports ]['file'];
			}
		}
		return $urls;
	}

	private static function cssUrls( string $entry ): array {
		$urls     = array();
		$manifest = self::getManifest();

		if ( ! empty( $manifest[ $entry ]['css'] ) ) {
			foreach ( $manifest[ $entry ]['css'] as $file ) {
				$urls[] = self::getPublicURLBase() . $file;
			}
		}
		return $urls;
	}
}