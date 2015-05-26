<?php
namespace photo_express;
if (!class_exists("Common")) {
    class Common
    {

        /**
         * Escape quotes to html entinty
         *
         * @param  <type> $str
         * @return <type>
         */
        static function escape($str)
        {
            $str = preg_replace('/"/', '&quot;', $str);
            $str = preg_replace("/'/", '&#039;', $str);
            return $str;
        }

        /**
         * Find tag in content
         *
         * @param string $content
         * @param string $tag
         * @param boolean $first Search only first. False by default
         * @return bool|string|array content of the found node. false if not found
         */
        static function get_item($content, $tag, $first = false)
        {
            if (!preg_match_all("|<$tag(?:\s[^>]+)?>(.+?)</$tag>|u", $content, $m, PREG_PATTERN_ORDER))
                return false;
//			echo "$tag: ".count($m[1])."<br/>";
            if (count($m[1]) > 1 && !$first) return ($m[1]);
            else return ($m[1][0]);
        }

        /**
         * Find tag in content by attribute
         *
         * @param string $content
         * @param string $tag
         * @param string $attr
         * @return string attribute value or all parameters if not found. false if no tag found
         */
        static function get_item_attr($content, $tag, $attr)
        {
            if (!preg_match("|<$tag\s+([^>]+)/?>|u", $content, $m))
                return false;
            $a = preg_split("/[\s=]/", $m[1]);
            for ($i = 0; $i < count($a); $i += 2) {
                if ($a[$i] == $attr) return trim($a[$i + 1], "'\" ");
            }
            return join(',', $a);
        }

	    /**
	     * Make the row from parameters for setting tables
	     */
	    static function make_settings_row($title, $content, $description = '', $title_pars = '', $description_pars = '')
	    {
		    ?>
		    <tr <?php echo $title_pars; ?>>
			    <th scope="row"><?php echo $title; ?></th>
			    <td>
				    <?php echo $content; ?>
				    <br/>
				    <span class="description" <?php echo $description_pars; ?>><?php echo $description; ?></span>
			    </td>
		    </tr>
	    <?php
	    }


    }
}