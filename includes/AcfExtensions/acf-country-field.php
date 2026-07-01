<?php
/**
 * Extension Name: ACF Country Field
 * Description: A searchable dropdown field populated with a comprehensive list of global countries.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'acf/include_field_types', function( $version ) {

    if ( ! class_exists( 'acf_field_select' ) ) {
        return;
    }

    class acf_field_country extends acf_field_select {

        public function initialize() {
            parent::initialize();

            $this->name     = 'country';
            $this->label    = __( 'Country', 'tka-site-utilities' );
            $this->category = 'choice';
            $this->defaults = array_merge( $this->defaults, array(
                'multiple'      => 0,
                'allow_null'    => 0,
                'choices'       => array(),
                'default_value' => '',
                'ui'            => 1,
                'ajax'          => 0,
                'placeholder'   => '',
                'return_format' => 'array',
            ) );
        }

        public function render_field_settings( $field ) {
            // Return Format
            acf_render_field_setting( $field, array(
                'label'        => __( 'Return Format', 'tka-site-utilities' ),
                'instructions' => __( 'Specify the returned value on front end', 'tka-site-utilities' ),
                'type'         => 'radio',
                'name'         => 'return_format',
                'layout'       => 'horizontal',
                'choices'      => array(
                    'array' => __( 'Country Code & Name (Array)', 'tka-site-utilities' ),
                    'value' => __( 'Country Code (e.g., CH)', 'tka-site-utilities' ),
                    'label' => __( 'Country Name (e.g., Switzerland)', 'tka-site-utilities' ),
                ),
            ) );

            // Default Value
            acf_render_field_setting( $field, array(
                'label'        => __( 'Default Value', 'tka-site-utilities' ),
                'instructions' => __( 'Enter the 2-letter country code (e.g., US, GB)', 'tka-site-utilities' ),
                'type'         => 'text',
                'name'         => 'default_value',
            ) );

            // Allow Null
            acf_render_field_setting( $field, array(
                'label'        => __( 'Allow Null?', 'tka-site-utilities' ),
                'instructions' => '',
                'name'         => 'allow_null',
                'type'         => 'true_false',
                'ui'           => 1,
            ) );

            // Multiple
            acf_render_field_setting( $field, array(
                'label'        => __( 'Select multiple values?', 'tka-site-utilities' ),
                'instructions' => '',
                'name'         => 'multiple',
                'type'         => 'true_false',
                'ui'           => 1,
            ) );

            // UI
            acf_render_field_setting( $field, array(
                'label'        => __( 'Stylised UI', 'tka-site-utilities' ),
                'instructions' => '',
                'name'         => 'ui',
                'type'         => 'true_false',
                'ui'           => 1,
            ) );
        }

        public function prepare_field( $field ) {
            // Force choices to be our country list
            $field['choices'] = $this->get_countries();
            return $field;
        }

        public function format_value( $value, $post_id, $field ) {
            // Bypass if empty
            if ( empty( $value ) ) {
                return $value;
            }

            // Always treat value as an array (to handle multiple values easily)
            $is_array = is_array( $value );
            $values = $is_array ? $value : array( $value );
            
            $countries = $this->get_countries();
            $formatted = array();

            foreach ( $values as $val ) {
                $label = isset( $countries[ $val ] ) ? $countries[ $val ] : $val;

                if ( $field['return_format'] === 'array' ) {
                    $formatted[] = array(
                        'value' => $val,
                        'label' => $label,
                    );
                } elseif ( $field['return_format'] === 'label' ) {
                    $formatted[] = $label;
                } else {
                    $formatted[] = $val;
                }
            }

            // Return array or single value
            if ( $field['multiple'] ) {
                return $formatted;
            }

            return $formatted[0] ?? null;
        }

        private function get_countries() {
            return array(
                'AF' => __( 'Afghanistan', 'tka-site-utilities' ),
                'AL' => __( 'Albania', 'tka-site-utilities' ),
                'DZ' => __( 'Algeria', 'tka-site-utilities' ),
                'AD' => __( 'Andorra', 'tka-site-utilities' ),
                'AO' => __( 'Angola', 'tka-site-utilities' ),
                'AG' => __( 'Antigua and Barbuda', 'tka-site-utilities' ),
                'AR' => __( 'Argentina', 'tka-site-utilities' ),
                'AM' => __( 'Armenia', 'tka-site-utilities' ),
                'AU' => __( 'Australia', 'tka-site-utilities' ),
                'AT' => __( 'Austria', 'tka-site-utilities' ),
                'AZ' => __( 'Azerbaijan', 'tka-site-utilities' ),
                'BS' => __( 'Bahamas', 'tka-site-utilities' ),
                'BH' => __( 'Bahrain', 'tka-site-utilities' ),
                'BD' => __( 'Bangladesh', 'tka-site-utilities' ),
                'BB' => __( 'Barbados', 'tka-site-utilities' ),
                'BY' => __( 'Belarus', 'tka-site-utilities' ),
                'BE' => __( 'Belgium', 'tka-site-utilities' ),
                'BZ' => __( 'Belize', 'tka-site-utilities' ),
                'BJ' => __( 'Benin', 'tka-site-utilities' ),
                'BT' => __( 'Bhutan', 'tka-site-utilities' ),
                'BO' => __( 'Bolivia', 'tka-site-utilities' ),
                'BA' => __( 'Bosnia and Herzegovina', 'tka-site-utilities' ),
                'BW' => __( 'Botswana', 'tka-site-utilities' ),
                'BR' => __( 'Brazil', 'tka-site-utilities' ),
                'BN' => __( 'Brunei', 'tka-site-utilities' ),
                'BG' => __( 'Bulgaria', 'tka-site-utilities' ),
                'BF' => __( 'Burkina Faso', 'tka-site-utilities' ),
                'BI' => __( 'Burundi', 'tka-site-utilities' ),
                'CV' => __( 'Cabo Verde', 'tka-site-utilities' ),
                'KH' => __( 'Cambodia', 'tka-site-utilities' ),
                'CM' => __( 'Cameroon', 'tka-site-utilities' ),
                'CA' => __( 'Canada', 'tka-site-utilities' ),
                'CF' => __( 'Central African Republic', 'tka-site-utilities' ),
                'TD' => __( 'Chad', 'tka-site-utilities' ),
                'CL' => __( 'Chile', 'tka-site-utilities' ),
                'CN' => __( 'China', 'tka-site-utilities' ),
                'CO' => __( 'Colombia', 'tka-site-utilities' ),
                'KM' => __( 'Comoros', 'tka-site-utilities' ),
                'CG' => __( 'Congo (Congo-Brazzaville)', 'tka-site-utilities' ),
                'CD' => __( 'Costa Rica', 'tka-site-utilities' ),
                'CI' => __( 'Côte d\'Ivoire', 'tka-site-utilities' ),
                'HR' => __( 'Croatia', 'tka-site-utilities' ),
                'CU' => __( 'Cuba', 'tka-site-utilities' ),
                'CY' => __( 'Cyprus', 'tka-site-utilities' ),
                'CZ' => __( 'Czechia (Czech Republic)', 'tka-site-utilities' ),
                'DK' => __( 'Denmark', 'tka-site-utilities' ),
                'DJ' => __( 'Djibouti', 'tka-site-utilities' ),
                'DM' => __( 'Dominica', 'tka-site-utilities' ),
                'DO' => __( 'Dominican Republic', 'tka-site-utilities' ),
                'EC' => __( 'Ecuador', 'tka-site-utilities' ),
                'EG' => __( 'Egypt', 'tka-site-utilities' ),
                'SV' => __( 'El Salvador', 'tka-site-utilities' ),
                'GQ' => __( 'Equatorial Guinea', 'tka-site-utilities' ),
                'ER' => __( 'Eritrea', 'tka-site-utilities' ),
                'EE' => __( 'Estonia', 'tka-site-utilities' ),
                'SZ' => __( 'Eswatini', 'tka-site-utilities' ),
                'ET' => __( 'Ethiopia', 'tka-site-utilities' ),
                'FJ' => __( 'Fiji', 'tka-site-utilities' ),
                'FI' => __( 'Finland', 'tka-site-utilities' ),
                'FR' => __( 'France', 'tka-site-utilities' ),
                'GA' => __( 'Gabon', 'tka-site-utilities' ),
                'GM' => __( 'Gambia', 'tka-site-utilities' ),
                'GE' => __( 'Georgia', 'tka-site-utilities' ),
                'DE' => __( 'Germany', 'tka-site-utilities' ),
                'GH' => __( 'Ghana', 'tka-site-utilities' ),
                'GR' => __( 'Greece', 'tka-site-utilities' ),
                'GD' => __( 'Grenada', 'tka-site-utilities' ),
                'GT' => __( 'Guatemala', 'tka-site-utilities' ),
                'GN' => __( 'Guinea', 'tka-site-utilities' ),
                'GW' => __( 'Guinea-Bissau', 'tka-site-utilities' ),
                'GY' => __( 'Guyana', 'tka-site-utilities' ),
                'HT' => __( 'Haiti', 'tka-site-utilities' ),
                'HN' => __( 'Honduras', 'tka-site-utilities' ),
                'HU' => __( 'Hungary', 'tka-site-utilities' ),
                'IS' => __( 'Iceland', 'tka-site-utilities' ),
                'IN' => __( 'India', 'tka-site-utilities' ),
                'ID' => __( 'Indonesia', 'tka-site-utilities' ),
                'IR' => __( 'Iran', 'tka-site-utilities' ),
                'IQ' => __( 'Iraq', 'tka-site-utilities' ),
                'IE' => __( 'Ireland', 'tka-site-utilities' ),
                'IL' => __( 'Israel', 'tka-site-utilities' ),
                'IT' => __( 'Italy', 'tka-site-utilities' ),
                'JM' => __( 'Jamaica', 'tka-site-utilities' ),
                'JP' => __( 'Japan', 'tka-site-utilities' ),
                'JO' => __( 'Jordan', 'tka-site-utilities' ),
                'KZ' => __( 'Kazakhstan', 'tka-site-utilities' ),
                'KE' => __( 'Kenya', 'tka-site-utilities' ),
                'KI' => __( 'Kiribati', 'tka-site-utilities' ),
                'KP' => __( 'North Korea', 'tka-site-utilities' ),
                'KR' => __( 'South Korea', 'tka-site-utilities' ),
                'KW' => __( 'Kuwait', 'tka-site-utilities' ),
                'KG' => __( 'Kyrgyzstan', 'tka-site-utilities' ),
                'LA' => __( 'Laos', 'tka-site-utilities' ),
                'LV' => __( 'Latvia', 'tka-site-utilities' ),
                'LB' => __( 'Lebanon', 'tka-site-utilities' ),
                'LS' => __( 'Lesotho', 'tka-site-utilities' ),
                'LR' => __( 'Liberia', 'tka-site-utilities' ),
                'LY' => __( 'Libya', 'tka-site-utilities' ),
                'LI' => __( 'Liechtenstein', 'tka-site-utilities' ),
                'LT' => __( 'Lithuania', 'tka-site-utilities' ),
                'LU' => __( 'Luxembourg', 'tka-site-utilities' ),
                'MG' => __( 'Madagascar', 'tka-site-utilities' ),
                'MW' => __( 'Malawi', 'tka-site-utilities' ),
                'MY' => __( 'Malaysia', 'tka-site-utilities' ),
                'MV' => __( 'Maldives', 'tka-site-utilities' ),
                'ML' => __( 'Mali', 'tka-site-utilities' ),
                'MT' => __( 'Malta', 'tka-site-utilities' ),
                'MH' => __( 'Marshall Islands', 'tka-site-utilities' ),
                'MR' => __( 'Mauritania', 'tka-site-utilities' ),
                'MU' => __( 'Mauritius', 'tka-site-utilities' ),
                'MX' => __( 'Mexico', 'tka-site-utilities' ),
                'FM' => __( 'Micronesia', 'tka-site-utilities' ),
                'MD' => __( 'Moldova', 'tka-site-utilities' ),
                'MC' => __( 'Monaco', 'tka-site-utilities' ),
                'MN' => __( 'Mongolia', 'tka-site-utilities' ),
                'ME' => __( 'Montenegro', 'tka-site-utilities' ),
                'MA' => __( 'Morocco', 'tka-site-utilities' ),
                'MZ' => __( 'Mozambique', 'tka-site-utilities' ),
                'MM' => __( 'Myanmar (Burma)', 'tka-site-utilities' ),
                'NA' => __( 'Namibia', 'tka-site-utilities' ),
                'NR' => __( 'Nauru', 'tka-site-utilities' ),
                'NP' => __( 'Nepal', 'tka-site-utilities' ),
                'NL' => __( 'Netherlands', 'tka-site-utilities' ),
                'NZ' => __( 'New Zealand', 'tka-site-utilities' ),
                'NI' => __( 'Nicaragua', 'tka-site-utilities' ),
                'NE' => __( 'Niger', 'tka-site-utilities' ),
                'NG' => __( 'Nigeria', 'tka-site-utilities' ),
                'MK' => __( 'North Macedonia', 'tka-site-utilities' ),
                'NO' => __( 'Norway', 'tka-site-utilities' ),
                'OM' => __( 'Oman', 'tka-site-utilities' ),
                'PK' => __( 'Pakistan', 'tka-site-utilities' ),
                'PW' => __( 'Palau', 'tka-site-utilities' ),
                'PA' => __( 'Panama', 'tka-site-utilities' ),
                'PG' => __( 'Papua New Guinea', 'tka-site-utilities' ),
                'PY' => __( 'Paraguay', 'tka-site-utilities' ),
                'PE' => __( 'Peru', 'tka-site-utilities' ),
                'PH' => __( 'Philippines', 'tka-site-utilities' ),
                'PL' => __( 'Poland', 'tka-site-utilities' ),
                'PT' => __( 'Portugal', 'tka-site-utilities' ),
                'QA' => __( 'Qatar', 'tka-site-utilities' ),
                'RO' => __( 'Romania', 'tka-site-utilities' ),
                'RU' => __( 'Russia', 'tka-site-utilities' ),
                'RW' => __( 'Rwanda', 'tka-site-utilities' ),
                'KN' => __( 'Saint Kitts and Nevis', 'tka-site-utilities' ),
                'LC' => __( 'Saint Lucia', 'tka-site-utilities' ),
                'VC' => __( 'Saint Vincent and the Grenadines', 'tka-site-utilities' ),
                'WS' => __( 'Samoa', 'tka-site-utilities' ),
                'SM' => __( 'San Marino', 'tka-site-utilities' ),
                'ST' => __( 'Sao Tome and Principe', 'tka-site-utilities' ),
                'SA' => __( 'Saudi Arabia', 'tka-site-utilities' ),
                'SN' => __( 'Senegal', 'tka-site-utilities' ),
                'RS' => __( 'Serbia', 'tka-site-utilities' ),
                'SC' => __( 'Seychelles', 'tka-site-utilities' ),
                'SL' => __( 'Sierra Leone', 'tka-site-utilities' ),
                'SG' => __( 'Singapore', 'tka-site-utilities' ),
                'SK' => __( 'Slovakia', 'tka-site-utilities' ),
                'SI' => __( 'Slovenia', 'tka-site-utilities' ),
                'SB' => __( 'Solomon Islands', 'tka-site-utilities' ),
                'SO' => __( 'Somalia', 'tka-site-utilities' ),
                'ZA' => __( 'South Africa', 'tka-site-utilities' ),
                'SS' => __( 'South Sudan', 'tka-site-utilities' ),
                'ES' => __( 'Spain', 'tka-site-utilities' ),
                'LK' => __( 'Sri Lanka', 'tka-site-utilities' ),
                'SD' => __( 'Sudan', 'tka-site-utilities' ),
                'SR' => __( 'Suriname', 'tka-site-utilities' ),
                'SE' => __( 'Sweden', 'tka-site-utilities' ),
                'CH' => __( 'Switzerland', 'tka-site-utilities' ),
                'SY' => __( 'Syria', 'tka-site-utilities' ),
                'TW' => __( 'Taiwan', 'tka-site-utilities' ),
                'TJ' => __( 'Tajikistan', 'tka-site-utilities' ),
                'TZ' => __( 'Tanzania', 'tka-site-utilities' ),
                'TH' => __( 'Thailand', 'tka-site-utilities' ),
                'TL' => __( 'Timor-Leste', 'tka-site-utilities' ),
                'TG' => __( 'Togo', 'tka-site-utilities' ),
                'TO' => __( 'Tonga', 'tka-site-utilities' ),
                'TT' => __( 'Trinidad and Tobago', 'tka-site-utilities' ),
                'TN' => __( 'Tunisia', 'tka-site-utilities' ),
                'TR' => __( 'Turkey', 'tka-site-utilities' ),
                'TM' => __( 'Turkmenistan', 'tka-site-utilities' ),
                'TV' => __( 'Tuvalu', 'tka-site-utilities' ),
                'UG' => __( 'Uganda', 'tka-site-utilities' ),
                'UA' => __( 'Ukraine', 'tka-site-utilities' ),
                'AE' => __( 'United Arab Emirates', 'tka-site-utilities' ),
                'GB' => __( 'United Kingdom', 'tka-site-utilities' ),
                'US' => __( 'United States', 'tka-site-utilities' ),
                'UY' => __( 'Uruguay', 'tka-site-utilities' ),
                'UZ' => __( 'Uzbekistan', 'tka-site-utilities' ),
                'VU' => __( 'Vanuatu', 'tka-site-utilities' ),
                'VA' => __( 'Vatican City', 'tka-site-utilities' ),
                'VE' => __( 'Venezuela', 'tka-site-utilities' ),
                'VN' => __( 'Vietnam', 'tka-site-utilities' ),
                'YE' => __( 'Yemen', 'tka-site-utilities' ),
                'ZM' => __( 'Zambia', 'tka-site-utilities' ),
                'ZW' => __( 'Zimbabwe', 'tka-site-utilities' ),
            );
        }
    }

    new acf_field_country();

} );
