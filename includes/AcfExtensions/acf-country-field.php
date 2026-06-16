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
            $this->label    = __( 'Country', 'acf' );
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
                'label'        => __( 'Return Format', 'acf' ),
                'instructions' => __( 'Specify the returned value on front end', 'acf' ),
                'type'         => 'radio',
                'name'         => 'return_format',
                'layout'       => 'horizontal',
                'choices'      => array(
                    'array' => __( 'Country Code & Name (Array)', 'acf' ),
                    'value' => __( 'Country Code (e.g., CH)', 'acf' ),
                    'label' => __( 'Country Name (e.g., Switzerland)', 'acf' ),
                ),
            ) );

            // Default Value
            acf_render_field_setting( $field, array(
                'label'        => __( 'Default Value', 'acf' ),
                'instructions' => __( 'Enter the 2-letter country code (e.g., US, GB)', 'acf' ),
                'type'         => 'text',
                'name'         => 'default_value',
            ) );

            // Allow Null
            acf_render_field_setting( $field, array(
                'label'        => __( 'Allow Null?', 'acf' ),
                'instructions' => '',
                'name'         => 'allow_null',
                'type'         => 'true_false',
                'ui'           => 1,
            ) );

            // Multiple
            acf_render_field_setting( $field, array(
                'label'        => __( 'Select multiple values?', 'acf' ),
                'instructions' => '',
                'name'         => 'multiple',
                'type'         => 'true_false',
                'ui'           => 1,
            ) );

            // UI
            acf_render_field_setting( $field, array(
                'label'        => __( 'Stylised UI', 'acf' ),
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
                'AF' => __( 'Afghanistan', 'acf' ),
                'AL' => __( 'Albania', 'acf' ),
                'DZ' => __( 'Algeria', 'acf' ),
                'AD' => __( 'Andorra', 'acf' ),
                'AO' => __( 'Angola', 'acf' ),
                'AG' => __( 'Antigua and Barbuda', 'acf' ),
                'AR' => __( 'Argentina', 'acf' ),
                'AM' => __( 'Armenia', 'acf' ),
                'AU' => __( 'Australia', 'acf' ),
                'AT' => __( 'Austria', 'acf' ),
                'AZ' => __( 'Azerbaijan', 'acf' ),
                'BS' => __( 'Bahamas', 'acf' ),
                'BH' => __( 'Bahrain', 'acf' ),
                'BD' => __( 'Bangladesh', 'acf' ),
                'BB' => __( 'Barbados', 'acf' ),
                'BY' => __( 'Belarus', 'acf' ),
                'BE' => __( 'Belgium', 'acf' ),
                'BZ' => __( 'Belize', 'acf' ),
                'BJ' => __( 'Benin', 'acf' ),
                'BT' => __( 'Bhutan', 'acf' ),
                'BO' => __( 'Bolivia', 'acf' ),
                'BA' => __( 'Bosnia and Herzegovina', 'acf' ),
                'BW' => __( 'Botswana', 'acf' ),
                'BR' => __( 'Brazil', 'acf' ),
                'BN' => __( 'Brunei', 'acf' ),
                'BG' => __( 'Bulgaria', 'acf' ),
                'BF' => __( 'Burkina Faso', 'acf' ),
                'BI' => __( 'Burundi', 'acf' ),
                'CV' => __( 'Cabo Verde', 'acf' ),
                'KH' => __( 'Cambodia', 'acf' ),
                'CM' => __( 'Cameroon', 'acf' ),
                'CA' => __( 'Canada', 'acf' ),
                'CF' => __( 'Central African Republic', 'acf' ),
                'TD' => __( 'Chad', 'acf' ),
                'CL' => __( 'Chile', 'acf' ),
                'CN' => __( 'China', 'acf' ),
                'CO' => __( 'Colombia', 'acf' ),
                'KM' => __( 'Comoros', 'acf' ),
                'CG' => __( 'Congo (Congo-Brazzaville)', 'acf' ),
                'CD' => __( 'Costa Rica', 'acf' ),
                'CI' => __( 'Côte d\'Ivoire', 'acf' ),
                'HR' => __( 'Croatia', 'acf' ),
                'CU' => __( 'Cuba', 'acf' ),
                'CY' => __( 'Cyprus', 'acf' ),
                'CZ' => __( 'Czechia (Czech Republic)', 'acf' ),
                'DK' => __( 'Denmark', 'acf' ),
                'DJ' => __( 'Djibouti', 'acf' ),
                'DM' => __( 'Dominica', 'acf' ),
                'DO' => __( 'Dominican Republic', 'acf' ),
                'EC' => __( 'Ecuador', 'acf' ),
                'EG' => __( 'Egypt', 'acf' ),
                'SV' => __( 'El Salvador', 'acf' ),
                'GQ' => __( 'Equatorial Guinea', 'acf' ),
                'ER' => __( 'Eritrea', 'acf' ),
                'EE' => __( 'Estonia', 'acf' ),
                'SZ' => __( 'Eswatini', 'acf' ),
                'ET' => __( 'Ethiopia', 'acf' ),
                'FJ' => __( 'Fiji', 'acf' ),
                'FI' => __( 'Finland', 'acf' ),
                'FR' => __( 'France', 'acf' ),
                'GA' => __( 'Gabon', 'acf' ),
                'GM' => __( 'Gambia', 'acf' ),
                'GE' => __( 'Georgia', 'acf' ),
                'DE' => __( 'Germany', 'acf' ),
                'GH' => __( 'Ghana', 'acf' ),
                'GR' => __( 'Greece', 'acf' ),
                'GD' => __( 'Grenada', 'acf' ),
                'GT' => __( 'Guatemala', 'acf' ),
                'GN' => __( 'Guinea', 'acf' ),
                'GW' => __( 'Guinea-Bissau', 'acf' ),
                'GY' => __( 'Guyana', 'acf' ),
                'HT' => __( 'Haiti', 'acf' ),
                'HN' => __( 'Honduras', 'acf' ),
                'HU' => __( 'Hungary', 'acf' ),
                'IS' => __( 'Iceland', 'acf' ),
                'IN' => __( 'India', 'acf' ),
                'ID' => __( 'Indonesia', 'acf' ),
                'IR' => __( 'Iran', 'acf' ),
                'IQ' => __( 'Iraq', 'acf' ),
                'IE' => __( 'Ireland', 'acf' ),
                'IL' => __( 'Israel', 'acf' ),
                'IT' => __( 'Italy', 'acf' ),
                'JM' => __( 'Jamaica', 'acf' ),
                'JP' => __( 'Japan', 'acf' ),
                'JO' => __( 'Jordan', 'acf' ),
                'KZ' => __( 'Kazakhstan', 'acf' ),
                'KE' => __( 'Kenya', 'acf' ),
                'KI' => __( 'Kiribati', 'acf' ),
                'KP' => __( 'North Korea', 'acf' ),
                'KR' => __( 'South Korea', 'acf' ),
                'KW' => __( 'Kuwait', 'acf' ),
                'KG' => __( 'Kyrgyzstan', 'acf' ),
                'LA' => __( 'Laos', 'acf' ),
                'LV' => __( 'Latvia', 'acf' ),
                'LB' => __( 'Lebanon', 'acf' ),
                'LS' => __( 'Lesotho', 'acf' ),
                'LR' => __( 'Liberia', 'acf' ),
                'LY' => __( 'Libya', 'acf' ),
                'LI' => __( 'Liechtenstein', 'acf' ),
                'LT' => __( 'Lithuania', 'acf' ),
                'LU' => __( 'Luxembourg', 'acf' ),
                'MG' => __( 'Madagascar', 'acf' ),
                'MW' => __( 'Malawi', 'acf' ),
                'MY' => __( 'Malaysia', 'acf' ),
                'MV' => __( 'Maldives', 'acf' ),
                'ML' => __( 'Mali', 'acf' ),
                'MT' => __( 'Malta', 'acf' ),
                'MH' => __( 'Marshall Islands', 'acf' ),
                'MR' => __( 'Mauritania', 'acf' ),
                'MU' => __( 'Mauritius', 'acf' ),
                'MX' => __( 'Mexico', 'acf' ),
                'FM' => __( 'Micronesia', 'acf' ),
                'MD' => __( 'Moldova', 'acf' ),
                'MC' => __( 'Monaco', 'acf' ),
                'MN' => __( 'Mongolia', 'acf' ),
                'ME' => __( 'Montenegro', 'acf' ),
                'MA' => __( 'Morocco', 'acf' ),
                'MZ' => __( 'Mozambique', 'acf' ),
                'MM' => __( 'Myanmar (Burma)', 'acf' ),
                'NA' => __( 'Namibia', 'acf' ),
                'NR' => __( 'Nauru', 'acf' ),
                'NP' => __( 'Nepal', 'acf' ),
                'NL' => __( 'Netherlands', 'acf' ),
                'NZ' => __( 'New Zealand', 'acf' ),
                'NI' => __( 'Nicaragua', 'acf' ),
                'NE' => __( 'Niger', 'acf' ),
                'NG' => __( 'Nigeria', 'acf' ),
                'MK' => __( 'North Macedonia', 'acf' ),
                'NO' => __( 'Norway', 'acf' ),
                'OM' => __( 'Oman', 'acf' ),
                'PK' => __( 'Pakistan', 'acf' ),
                'PW' => __( 'Palau', 'acf' ),
                'PA' => __( 'Panama', 'acf' ),
                'PG' => __( 'Papua New Guinea', 'acf' ),
                'PY' => __( 'Paraguay', 'acf' ),
                'PE' => __( 'Peru', 'acf' ),
                'PH' => __( 'Philippines', 'acf' ),
                'PL' => __( 'Poland', 'acf' ),
                'PT' => __( 'Portugal', 'acf' ),
                'QA' => __( 'Qatar', 'acf' ),
                'RO' => __( 'Romania', 'acf' ),
                'RU' => __( 'Russia', 'acf' ),
                'RW' => __( 'Rwanda', 'acf' ),
                'KN' => __( 'Saint Kitts and Nevis', 'acf' ),
                'LC' => __( 'Saint Lucia', 'acf' ),
                'VC' => __( 'Saint Vincent and the Grenadines', 'acf' ),
                'WS' => __( 'Samoa', 'acf' ),
                'SM' => __( 'San Marino', 'acf' ),
                'ST' => __( 'Sao Tome and Principe', 'acf' ),
                'SA' => __( 'Saudi Arabia', 'acf' ),
                'SN' => __( 'Senegal', 'acf' ),
                'RS' => __( 'Serbia', 'acf' ),
                'SC' => __( 'Seychelles', 'acf' ),
                'SL' => __( 'Sierra Leone', 'acf' ),
                'SG' => __( 'Singapore', 'acf' ),
                'SK' => __( 'Slovakia', 'acf' ),
                'SI' => __( 'Slovenia', 'acf' ),
                'SB' => __( 'Solomon Islands', 'acf' ),
                'SO' => __( 'Somalia', 'acf' ),
                'ZA' => __( 'South Africa', 'acf' ),
                'SS' => __( 'South Sudan', 'acf' ),
                'ES' => __( 'Spain', 'acf' ),
                'LK' => __( 'Sri Lanka', 'acf' ),
                'SD' => __( 'Sudan', 'acf' ),
                'SR' => __( 'Suriname', 'acf' ),
                'SE' => __( 'Sweden', 'acf' ),
                'CH' => __( 'Switzerland', 'acf' ),
                'SY' => __( 'Syria', 'acf' ),
                'TW' => __( 'Taiwan', 'acf' ),
                'TJ' => __( 'Tajikistan', 'acf' ),
                'TZ' => __( 'Tanzania', 'acf' ),
                'TH' => __( 'Thailand', 'acf' ),
                'TL' => __( 'Timor-Leste', 'acf' ),
                'TG' => __( 'Togo', 'acf' ),
                'TO' => __( 'Tonga', 'acf' ),
                'TT' => __( 'Trinidad and Tobago', 'acf' ),
                'TN' => __( 'Tunisia', 'acf' ),
                'TR' => __( 'Turkey', 'acf' ),
                'TM' => __( 'Turkmenistan', 'acf' ),
                'TV' => __( 'Tuvalu', 'acf' ),
                'UG' => __( 'Uganda', 'acf' ),
                'UA' => __( 'Ukraine', 'acf' ),
                'AE' => __( 'United Arab Emirates', 'acf' ),
                'GB' => __( 'United Kingdom', 'acf' ),
                'US' => __( 'United States', 'acf' ),
                'UY' => __( 'Uruguay', 'acf' ),
                'UZ' => __( 'Uzbekistan', 'acf' ),
                'VU' => __( 'Vanuatu', 'acf' ),
                'VA' => __( 'Vatican City', 'acf' ),
                'VE' => __( 'Venezuela', 'acf' ),
                'VN' => __( 'Vietnam', 'acf' ),
                'YE' => __( 'Yemen', 'acf' ),
                'ZM' => __( 'Zambia', 'acf' ),
                'ZW' => __( 'Zimbabwe', 'acf' ),
            );
        }
    }

    new acf_field_country();

} );
