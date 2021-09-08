<?php


return [
    'dev'=>[
        'company'=>[
            "ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "ID"
            ],
            "TITLE"=> [
                "type"=> "string",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Company Name"
            ],
            "COMPANY_TYPE"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "COMPANY_TYPE",
                "title"=> "Company type"
            ],
            "LOGO"=> [
                "type"=> "file",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Logo"
            ],
            "ADDRESS"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Street address"
            ],
            "ADDRESS_2"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Address (line 2)"
            ],
            "ADDRESS_CITY"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "City"
            ],
            "ADDRESS_POSTAL_CODE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Zip"
            ],
            "ADDRESS_REGION"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Region"
            ],
            "ADDRESS_PROVINCE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "State / Province"
            ],
            "ADDRESS_COUNTRY"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Country"
            ],
            "ADDRESS_COUNTRY_CODE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Country Code"
            ],
            "ADDRESS_LOC_ADDR_ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Location address ID"
            ],
            "ADDRESS_LEGAL"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Legal address"
            ],
            "REG_ADDRESS"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Address"
            ],
            "REG_ADDRESS_2"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Address (line 2)"
            ],
            "REG_ADDRESS_CITY"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing City"
            ],
            "REG_ADDRESS_POSTAL_CODE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Zip"
            ],
            "REG_ADDRESS_REGION"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Region"
            ],
            "REG_ADDRESS_PROVINCE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing State / Province"
            ],
            "REG_ADDRESS_COUNTRY"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Country"
            ],
            "REG_ADDRESS_COUNTRY_CODE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Country Code"
            ],
            "REG_ADDRESS_LOC_ADDR_ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Legal address location address ID"
            ],
            "BANKING_DETAILS"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Payment details"
            ],
            "INDUSTRY"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "INDUSTRY",
                "title"=> "Industry"
            ],
            "EMPLOYEES"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "EMPLOYEES",
                "title"=> "Employees"
            ],
            "CURRENCY_ID"=> [
                "type"=> "crm_currency",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Currency"
            ],
            "REVENUE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Annual revenue"
            ],
            "OPENED"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Available to everyone"
            ],
            "COMMENTS"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Comment"
            ],
            "HAS_PHONE"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Has phone"
            ],
            "HAS_EMAIL"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Has email"
            ],
            "HAS_IMOL"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Has Open Channel"
            ],
            "IS_MY_COMPANY"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "My Company"
            ],
            "ASSIGNED_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Responsible person"
            ],
            "CREATED_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Created by"
            ],
            "MODIFY_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Modified by"
            ],
            "DATE_CREATE"=> [
                "type"=> "datetime",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Created on"
            ],
            "DATE_MODIFY"=> [
                "type"=> "datetime",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Modified on"
            ],
            "CONTACT_ID"=> [
                "type"=> "crm_contact",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "Contact"
            ],
            "LEAD_ID"=> [
                "type"=> "crm_lead",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Lead"
            ],
            "ORIGINATOR_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "External source"
            ],
            "ORIGIN_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Item ID in data source"
            ],
            "ORIGIN_VERSION"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Original version"
            ],
            "UTM_SOURCE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Ad system"
            ],
            "UTM_MEDIUM"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Medium"
            ],
            "UTM_CAMPAIGN"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Ad campaign UTM"
            ],
            "UTM_CONTENT"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Campaign contents"
            ],
            "UTM_TERM"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Campaign search term"
            ],
            "PHONE"=> [
                "type"=> "crm_multifield",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "Phone"
            ],
            "EMAIL"=> [
                "type"=> "crm_multifield",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "E-mail"
            ],
            "WEB"=> [
                "type"=> "crm_multifield",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "Website"
            ],
            "IM"=> [
                "type"=> "crm_multifield",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "Messenger"
            ],
            "nick_name"=> [
                "value"=>"UF_CRM_1629181078",
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629181078",
                "listLabel"=> "NICKNAME",
                "formLabel"=> "NICKNAME",
                "filterLabel"=> "NICKNAME"
            ]
        ],
        'product'=>[
            "ID"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>true,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"ID"
            ],
            "CATALOG_ID"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>true,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Catalog"
            ],
            "PRICE"=>[
                "type"=>"double",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Price"
            ],
            "CURRENCY_ID"=>[
                "type"=>"string",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Currency"
            ],
            "NAME"=>[
                "type"=>"string",
                "isRequired"=>true,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Name"
            ],
            "CODE"=>[
                "type"=>"string",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"CODE"
            ],
            "DESCRIPTION"=>[
                "type"=>"string",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Description"
            ],
            "DESCRIPTION_TYPE"=>[
                "type"=>"string",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Description type"
            ],
            "ACTIVE"=>[
                "type"=>"char",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Active"
            ],
            "SECTION_ID"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Section"
            ],
            "SORT"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Sort"
            ],
            "VAT_ID"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Tax rate"
            ],
            "VAT_INCLUDED"=>[
                "type"=>"char",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Tax included"
            ],
            "MEASURE"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Unit of measurement"
            ],
            "XML_ID"=>[
                "type"=>"string",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"External ID"
            ],
            "PREVIEW_PICTURE"=>[
                "type"=>"product_file",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Preview image"
            ],
            "DETAIL_PICTURE"=>[
                "type"=>"product_file",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Full image"
            ],
            "DATE_CREATE"=>[
                "type"=>"datetime",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Created on"
            ],
            "TIMESTAMP_X"=>[
                "type"=>"datetime",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>true,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Modified on"
            ],
            "MODIFIED_BY"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Modified by"
            ],
            "CREATED_BY"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Created by"
            ]
        ],
        'deal'=>[
            "ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "ID"
            ],
            "TITLE"=> [
                "type"=> "string",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Name"
            ],
            "TYPE_ID"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "DEAL_TYPE",
                "title"=> "Type"
            ],
            "CATEGORY_ID"=> [
                "type"=> "crm_category",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> true,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Pipeline"
            ],
            "STAGE_ID"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "DEAL_STAGE",
                "title"=> "Deal stage"
            ],
            "STAGE_SEMANTIC_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Stage group"
            ],
            "IS_NEW"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "New deal"
            ],
            "IS_RECURRING"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Recurring deal"
            ],
            "IS_RETURN_CUSTOMER"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Repeat deal"
            ],
            "IS_REPEATED_APPROACH"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Repeat inquiry"
            ],
            "PROBABILITY"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Probability"
            ],
            "CURRENCY_ID"=> [
                "type"=> "crm_currency",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Currency"
            ],
            "OPPORTUNITY"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Total"
            ],
            "IS_MANUAL_OPPORTUNITY"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "IS_MANUAL_OPPORTUNITY"
            ],
            "TAX_VALUE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Tax rate"
            ],
            "COMPANY_ID"=> [
                "type"=> "crm_company",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Company"
            ],
            "CONTACT_ID"=> [
                "type"=> "crm_contact",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "isDeprecated"=> true,
                "title"=> "Contact"
            ],
            "CONTACT_IDS"=> [
                "type"=> "crm_contact",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "Contacts"
            ],
            "QUOTE_ID"=> [
                "type"=> "crm_quote",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Quote"
            ],
            "BEGINDATE"=> [
                "type"=> "date",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Start date"
            ],
            "CLOSEDATE"=> [
                "type"=> "date",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "End date"
            ],
            "OPENED"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Available to everyone"
            ],
            "CLOSED"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Closed"
            ],
            "COMMENTS"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Comment"
            ],
            "ASSIGNED_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Responsible person"
            ],
            "CREATED_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Created by"
            ],
            "MODIFY_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Modified by"
            ],
            "DATE_CREATE"=> [
                "type"=> "datetime",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Created on"
            ],
            "DATE_MODIFY"=> [
                "type"=> "datetime",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Modified on"
            ],
            "SOURCE_ID"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "SOURCE",
                "title"=> "Source"
            ],
            "SOURCE_DESCRIPTION"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Source information"
            ],
            "LEAD_ID"=> [
                "type"=> "crm_lead",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Lead"
            ],
            "ADDITIONAL_INFO"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Additional information"
            ],
            "LOCATION_ID"=> [
                "type"=> "location",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Location"
            ],
            "ORIGINATOR_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "External source"
            ],
            "ORIGIN_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Item ID in data source"
            ],
            "UTM_SOURCE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Ad system"
            ],
            "UTM_MEDIUM"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Medium"
            ],
            "UTM_CAMPAIGN"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Ad campaign UTM"
            ],
            "UTM_CONTENT"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Campaign contents"
            ],
            "UTM_TERM"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Campaign search term"
            ],
            "special_price"=> [
                "value"=>'UF_CRM_1628733612424',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628733612424",
                "listLabel"=> "Special price",
                "formLabel"=> "Special price",
                "filterLabel"=> "Special price"
            ],
            "shop_discount_price"=> [
                "value"=>'UF_CRM_1628733649125',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628733649125",
                "listLabel"=> "Shop discount price",
                "formLabel"=> "Shop discount price",
                "filterLabel"=> "Shop discount price"
            ],
            "discount_used"=> [
                "value"=>'UF_CRM_1628733813318',
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628733813318",
                "listLabel"=> "Discount Used",
                "formLabel"=> "Discount Used",
                "filterLabel"=> "Discount Used"
            ],
            "packaging_free"=> [
                "value"=>'UF_CRM_1628733998830',
                "type"=> "money",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628733998830",
                "listLabel"=> "Package fee",
                "formLabel"=> "Package fee",
                "filterLabel"=> "Package fee"
            ],
            "packaging_fee"=> [
                "value"=>'UF_CRM_1628734031097',
                "type"=> "boolean",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628734031097",
                "listLabel"=> "Package fee free？",
                "formLabel"=> "Package fee free？",
                "filterLabel"=> "Package fee free？"
            ],
            "delivery_fee"=> [
                "value"=>'UF_CRM_1628734060152',
                "type"=> "money",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628734060152",
                "listLabel"=> "Delivery fee",
                "formLabel"=> "Delivery fee",
                "filterLabel"=> "Delivery fee"
            ],
            "delivery_free"=> [
                "value"=>'UF_CRM_1628734075984',
                "type"=> "boolean",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628734075984",
                "listLabel"=> "Delivery fee free？",
                "formLabel"=> "Delivery fee free？",
                "filterLabel"=> "Delivery fee free？"
            ],
            "order_price_dish"=> [
                "value"=>'UF_CRM_1628734746554',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628734746554",
                "listLabel"=> "Order Price (dish)",
                "formLabel"=> "Order Price (dish)",
                "filterLabel"=> "Order Price (dish)"
            ],
            "promo_code"=> [
                "value"=>'UF_CRM_1628735337461',
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628735337461",
                "listLabel"=> "Promo code",
                "formLabel"=> "Promo code",
                "filterLabel"=> "Promo code"
            ],
            "shop_order_price"=> [
                "value"=>'UF_CRM_1629098340599',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629098340599",
                "listLabel"=> "Shop Order Price (package fee)",
                "formLabel"=> "Shop Order Price (package fee)",
                "filterLabel"=> "Shop Order Price (package fee)"
            ],
            "receive_money"=> [
                "value"=>'UF_CRM_1629103354670',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629103354670",
                "listLabel"=> "Fees paid by users",
                "formLabel"=> "Fees paid by users",
                "filterLabel"=> "Fees paid by users"
            ],
            "is_receive_money"=> [
                "value"=>'UF_CRM_1629103387129',
                "type"=> "boolean",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629103387129",
                "listLabel"=> "Does the user pay?",
                "formLabel"=> "Does the user pay?",
                "filterLabel"=> "Does the user pay?"
            ],
            "order_id"=> [
                "value"=>'UF_CRM_1629192007',
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629192007",
                "listLabel"=> "ORDER_ID",
                "formLabel"=> "ORDER_ID",
                "filterLabel"=> "ORDER_ID"
            ],
            "total_price_paid"=> [
                "value"=>'UF_CRM_1629271456064',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629271456064",
                "listLabel"=> "Total price Paid",
                "formLabel"=> "Total price Paid",
                "filterLabel"=> "Total price Paid"
            ],
            "platform_type"=> [
                "value"=>'UF_CRM_1629274022921',
                "type"=> "enumeration",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "items"=> [
                    [
                        "ID"=> "45",
                        "VALUE"=> "Call"
                    ],
                    [
                        "ID"=> "47",
                        "VALUE"=> "Android"
                    ],
                    [
                        "ID"=> "49",
                        "VALUE"=> "Android_lite"
                    ],
                    [
                        "ID"=> "51",
                        "VALUE"=> "IOS"
                    ],
                    [
                        "ID"=> "53",
                        "VALUE"=> "Web"
                    ],
                    [
                        "ID"=> "55",
                        "VALUE"=> "Telegram Bot"
                    ]
                ],
                "title"=> "UF_CRM_1629274022921",
                "listLabel"=> "Platform type",
                "formLabel"=> "Platform type",
                "filterLabel"=> "Platform type"
            ],
            "restaurant"=> [
                "value"=>'UF_CRM_1629461733965',
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629461733965",
                "listLabel"=> "Restaurant",
                "formLabel"=> "Restaurant",
                "filterLabel"=> "Restaurant"
            ],
            "is_new"=> [
                "value"=>'UF_CRM_1629539415',
                "type"=> "boolean",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629539415",
                "listLabel"=> "IS NEW",
                "formLabel"=> "IS NEW",
                "filterLabel"=> "IS NEW"
            ],
            "is_update"=> [
                "value"=>'UF_CRM_1629555411',
                "type"=> "boolean",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629555411",
                "listLabel"=> "IS UPDATE",
                "formLabel"=> "IS UPDATE",
                "filterLabel"=> "IS UPDATE"
            ],
            "is_field_update"=> [
                "value"=>'UF_CRM_1629593448',
                "type"=> "boolean",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629593448",
                "listLabel"=> "IS FIELD UPDATE",
                "formLabel"=> "IS FIELD UPDATE",
                "filterLabel"=> "IS FIELD UPDATE"
            ],
            "reason"=>[
                "value"=>'UF_CRM_1630462597',
                "type" => "string",
                "isRequired" => false,
                "isReadOnly" => false,
                "isImmutable" => false,
                "isMultiple" => false,
                "isDynamic" => true,
                "title" => "UF_CRM_1630462597",
                "listLabel" => "Reason",
                "formLabel" => "Reason",
                "filterLabel" => "Reason"
            ],
            "gross_profit"=>[
                "value"=>'UF_CRM_1630463379',
                "type" => "money",
                "isRequired" => false,
                "isReadOnly" => false,
                "isImmutable" => false,
                "isMultiple" => false,
                "isDynamic" => true,
                "title" => "UF_CRM_1630463379",
                "listLabel" => "Gross profit",
                "formLabel" => "Gross profit",
                "filterLabel" => "Gross profit"
            ],
            "income"=>[
                "value"=>'UF_CRM_1630463416',
                "type" => "money",
                "isRequired" => false,
                "isReadOnly" => false,
                "isImmutable" => false,
                "isMultiple" => false,
                "isDynamic" => true,
                "title" => "UF_CRM_1630463416",
                "listLabel" => "Income",
                "formLabel" => "Income",
                "filterLabel" => "Income"
            ],
            "brokerage_percentage"=>[
                "value"=>'UF_CRM_1630463561',
                "type" => "double",
                "isRequired" => false,
                "isReadOnly" => false,
                "isImmutable" => false,
                "isMultiple" => false,
                "isDynamic" => true,
                "title" => "UF_CRM_1630463561",
                "listLabel" => "Brokerage(%)",
                "formLabel" => "Brokerage(%)",
                "filterLabel" => "Brokerage(%)",
            ],
            "brokerage"=>[
                "value"=>'UF_CRM_1630463595',
                "type" => "money",
                "isRequired" => false,
                "isReadOnly" => false,
                "isImmutable" => false,
                "isMultiple" => false,
                "isDynamic" => true,
                "title" => "UF_CRM_1630463595",
                "listLabel" => "Brokerage",
                "formLabel" => "Brokerage",
                "filterLabel" => "Brokerage"
            ]
        ],
        'product_section'=>[
            "ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "ID"
            ],
            "CATALOG_ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> true,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Catalog"
            ],
            "SECTION_ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Section"
            ],
            "NAME"=> [
                "type"=> "string",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Name"
            ],
            "XML_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "External ID"
            ],
            "CODE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Mnemonic code"
            ]
        ],
        'deal_product'=>[
            "ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "ID"
            ],
            "OWNER_ID"=> [
                "type"=> "integer",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Owner ID"
            ],
            "OWNER_TYPE"=> [
                "type"=> "string",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Owner type"
            ],
            "PRODUCT_ID"=> [
                "type"=> "integer",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Product"
            ],
            "PRODUCT_NAME"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Product name"
            ],
            "PRICE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Price"
            ],
            "PRICE_EXCLUSIVE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Discounted price without tax"
            ],
            "PRICE_NETTO"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "PRICE_NETTO"
            ],
            "PRICE_BRUTTO"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "PRICE_BRUTTO"
            ],
            "QUANTITY"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Quantity"
            ],
            "DISCOUNT_TYPE_ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Discount type"
            ],
            "DISCOUNT_RATE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Discount value"
            ],
            "DISCOUNT_SUM"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Discount amount"
            ],
            "TAX_RATE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Tax"
            ],
            "TAX_INCLUDED"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Tax included"
            ],
            "CUSTOMIZED"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Modified on"
            ],
            "MEASURE_CODE"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Unit of measurement code"
            ],
            "MEASURE_NAME"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Unit of measurement"
            ],
            "SORT"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Sort"
            ]
        ]
    ],
    'prod'=>[
        'company'=>[
            "ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "ID"
            ],
            "TITLE"=> [
                "type"=> "string",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Company Name"
            ],
            "COMPANY_TYPE"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "COMPANY_TYPE",
                "title"=> "Company type"
            ],
            "LOGO"=> [
                "type"=> "file",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Logo"
            ],
            "ADDRESS"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Street address"
            ],
            "ADDRESS_2"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Address (line 2)"
            ],
            "ADDRESS_CITY"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "City"
            ],
            "ADDRESS_POSTAL_CODE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Zip"
            ],
            "ADDRESS_REGION"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Region"
            ],
            "ADDRESS_PROVINCE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "State / Province"
            ],
            "ADDRESS_COUNTRY"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Country"
            ],
            "ADDRESS_COUNTRY_CODE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Country Code"
            ],
            "ADDRESS_LOC_ADDR_ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Location address ID"
            ],
            "ADDRESS_LEGAL"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Legal address"
            ],
            "REG_ADDRESS"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Address"
            ],
            "REG_ADDRESS_2"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Address (line 2)"
            ],
            "REG_ADDRESS_CITY"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing City"
            ],
            "REG_ADDRESS_POSTAL_CODE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Zip"
            ],
            "REG_ADDRESS_REGION"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Region"
            ],
            "REG_ADDRESS_PROVINCE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing State / Province"
            ],
            "REG_ADDRESS_COUNTRY"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Country"
            ],
            "REG_ADDRESS_COUNTRY_CODE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Billing Country Code"
            ],
            "REG_ADDRESS_LOC_ADDR_ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Legal address location address ID"
            ],
            "BANKING_DETAILS"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Payment details"
            ],
            "INDUSTRY"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "INDUSTRY",
                "title"=> "Industry"
            ],
            "EMPLOYEES"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "EMPLOYEES",
                "title"=> "Employees"
            ],
            "CURRENCY_ID"=> [
                "type"=> "crm_currency",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Currency"
            ],
            "REVENUE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Annual revenue"
            ],
            "OPENED"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Available to everyone"
            ],
            "COMMENTS"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Comment"
            ],
            "HAS_PHONE"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Has phone"
            ],
            "HAS_EMAIL"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Has email"
            ],
            "HAS_IMOL"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Has Open Channel"
            ],
            "IS_MY_COMPANY"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "My Company"
            ],
            "ASSIGNED_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Responsible person"
            ],
            "CREATED_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Created by"
            ],
            "MODIFY_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Modified by"
            ],
            "DATE_CREATE"=> [
                "type"=> "datetime",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Created on"
            ],
            "DATE_MODIFY"=> [
                "type"=> "datetime",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Modified on"
            ],
            "CONTACT_ID"=> [
                "type"=> "crm_contact",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "Contact"
            ],
            "LEAD_ID"=> [
                "type"=> "crm_lead",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Lead"
            ],
            "ORIGINATOR_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "External source"
            ],
            "ORIGIN_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Item ID in data source"
            ],
            "ORIGIN_VERSION"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Original version"
            ],
            "UTM_SOURCE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Ad system"
            ],
            "UTM_MEDIUM"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Medium"
            ],
            "UTM_CAMPAIGN"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Ad campaign UTM"
            ],
            "UTM_CONTENT"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Campaign contents"
            ],
            "UTM_TERM"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Campaign search term"
            ],
            "PHONE"=> [
                "type"=> "crm_multifield",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "Phone"
            ],
            "EMAIL"=> [
                "type"=> "crm_multifield",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "E-mail"
            ],
            "WEB"=> [
                "type"=> "crm_multifield",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "Website"
            ],
            "IM"=> [
                "type"=> "crm_multifield",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "Messenger"
            ],
            "nick_name"=> [
                "value"=>"UF_CRM_1629181078",
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629181078",
                "listLabel"=> "NICKNAME",
                "formLabel"=> "NICKNAME",
                "filterLabel"=> "NICKNAME"
            ]
        ],
        'product'=>[
            "ID"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>true,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"ID"
            ],
            "CATALOG_ID"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>true,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Catalog"
            ],
            "PRICE"=>[
                "type"=>"double",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Price"
            ],
            "CURRENCY_ID"=>[
                "type"=>"string",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Currency"
            ],
            "NAME"=>[
                "type"=>"string",
                "isRequired"=>true,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Name"
            ],
            "CODE"=>[
                "type"=>"string",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"CODE"
            ],
            "DESCRIPTION"=>[
                "type"=>"string",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Description"
            ],
            "DESCRIPTION_TYPE"=>[
                "type"=>"string",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Description type"
            ],
            "ACTIVE"=>[
                "type"=>"char",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Active"
            ],
            "SECTION_ID"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Section"
            ],
            "SORT"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Sort"
            ],
            "VAT_ID"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Tax rate"
            ],
            "VAT_INCLUDED"=>[
                "type"=>"char",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Tax included"
            ],
            "MEASURE"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Unit of measurement"
            ],
            "XML_ID"=>[
                "type"=>"string",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"External ID"
            ],
            "PREVIEW_PICTURE"=>[
                "type"=>"product_file",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Preview image"
            ],
            "DETAIL_PICTURE"=>[
                "type"=>"product_file",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Full image"
            ],
            "DATE_CREATE"=>[
                "type"=>"datetime",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Created on"
            ],
            "TIMESTAMP_X"=>[
                "type"=>"datetime",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>true,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Modified on"
            ],
            "MODIFIED_BY"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Modified by"
            ],
            "CREATED_BY"=>[
                "type"=>"integer",
                "isRequired"=>false,
                "isReadOnly"=>false,
                "isImmutable"=>false,
                "isMultiple"=>false,
                "isDynamic"=>false,
                "title"=>"Created by"
            ]
        ],
        'deal'=>[
            "ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "ID"
            ],
            "TITLE"=> [
                "type"=> "string",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Name"
            ],
            "TYPE_ID"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "DEAL_TYPE",
                "title"=> "Type"
            ],
            "CATEGORY_ID"=> [
                "type"=> "crm_category",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> true,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Pipeline"
            ],
            "STAGE_ID"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "DEAL_STAGE",
                "title"=> "Deal stage"
            ],
            "STAGE_SEMANTIC_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Stage group"
            ],
            "IS_NEW"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "New deal"
            ],
            "IS_RECURRING"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Recurring deal"
            ],
            "IS_RETURN_CUSTOMER"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Repeat deal"
            ],
            "IS_REPEATED_APPROACH"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Repeat inquiry"
            ],
            "PROBABILITY"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Probability"
            ],
            "CURRENCY_ID"=> [
                "type"=> "crm_currency",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Currency"
            ],
            "OPPORTUNITY"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Total"
            ],
            "IS_MANUAL_OPPORTUNITY"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "IS_MANUAL_OPPORTUNITY"
            ],
            "TAX_VALUE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Tax rate"
            ],
            "COMPANY_ID"=> [
                "type"=> "crm_company",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Company"
            ],
            "CONTACT_ID"=> [
                "type"=> "crm_contact",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "isDeprecated"=> true,
                "title"=> "Contact"
            ],
            "CONTACT_IDS"=> [
                "type"=> "crm_contact",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> true,
                "isDynamic"=> false,
                "title"=> "Contacts"
            ],
            "QUOTE_ID"=> [
                "type"=> "crm_quote",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Quote"
            ],
            "BEGINDATE"=> [
                "type"=> "date",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Start date"
            ],
            "CLOSEDATE"=> [
                "type"=> "date",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "End date"
            ],
            "OPENED"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Available to everyone"
            ],
            "CLOSED"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Closed"
            ],
            "COMMENTS"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Comment"
            ],
            "ASSIGNED_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Responsible person"
            ],
            "CREATED_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Created by"
            ],
            "MODIFY_BY_ID"=> [
                "type"=> "user",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Modified by"
            ],
            "DATE_CREATE"=> [
                "type"=> "datetime",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Created on"
            ],
            "DATE_MODIFY"=> [
                "type"=> "datetime",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Modified on"
            ],
            "SOURCE_ID"=> [
                "type"=> "crm_status",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "statusType"=> "SOURCE",
                "title"=> "Source"
            ],
            "SOURCE_DESCRIPTION"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Source information"
            ],
            "LEAD_ID"=> [
                "type"=> "crm_lead",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Lead"
            ],
            "ADDITIONAL_INFO"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Additional information"
            ],
            "LOCATION_ID"=> [
                "type"=> "location",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Location"
            ],
            "ORIGINATOR_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "External source"
            ],
            "ORIGIN_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Item ID in data source"
            ],
            "UTM_SOURCE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Ad system"
            ],
            "UTM_MEDIUM"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Medium"
            ],
            "UTM_CAMPAIGN"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Ad campaign UTM"
            ],
            "UTM_CONTENT"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Campaign contents"
            ],
            "UTM_TERM"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Campaign search term"
            ],
            "special_price"=> [
                "value"=>'UF_CRM_1628733612424',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628733612424",
                "listLabel"=> "Special price",
                "formLabel"=> "Special price",
                "filterLabel"=> "Special price"
            ],
            "shop_discount_price"=> [
                "value"=>'UF_CRM_1628733649125',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628733649125",
                "listLabel"=> "Shop discount price",
                "formLabel"=> "Shop discount price",
                "filterLabel"=> "Shop discount price"
            ],
            "discount_used"=> [
                "value"=>'UF_CRM_1628733813318',
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628733813318",
                "listLabel"=> "Discount Used",
                "formLabel"=> "Discount Used",
                "filterLabel"=> "Discount Used"
            ],
            "packaging_free"=> [
                "value"=>'UF_CRM_1628733998830',
                "type"=> "money",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628733998830",
                "listLabel"=> "Package fee",
                "formLabel"=> "Package fee",
                "filterLabel"=> "Package fee"
            ],
            "packaging_fee"=> [
                "value"=>'UF_CRM_1628734031097',
                "type"=> "boolean",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628734031097",
                "listLabel"=> "Package fee free？",
                "formLabel"=> "Package fee free？",
                "filterLabel"=> "Package fee free？"
            ],
            "delivery_fee"=> [
                "value"=>'UF_CRM_1628734060152',
                "type"=> "money",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628734060152",
                "listLabel"=> "Delivery fee",
                "formLabel"=> "Delivery fee",
                "filterLabel"=> "Delivery fee"
            ],
            "delivery_free"=> [
                "value"=>'UF_CRM_1628734075984',
                "type"=> "boolean",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628734075984",
                "listLabel"=> "Delivery fee free？",
                "formLabel"=> "Delivery fee free？",
                "filterLabel"=> "Delivery fee free？"
            ],
            "order_price_dish"=> [
                "value"=>'UF_CRM_1628734746554',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628734746554",
                "listLabel"=> "Order Price (dish)",
                "formLabel"=> "Order Price (dish)",
                "filterLabel"=> "Order Price (dish)"
            ],
            "promo_code"=> [
                "value"=>'UF_CRM_1628735337461',
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1628735337461",
                "listLabel"=> "Promo code",
                "formLabel"=> "Promo code",
                "filterLabel"=> "Promo code"
            ],
            "shop_order_price"=> [
                "value"=>'UF_CRM_1629098340599',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629098340599",
                "listLabel"=> "Shop Order Price (package fee)",
                "formLabel"=> "Shop Order Price (package fee)",
                "filterLabel"=> "Shop Order Price (package fee)"
            ],
            "receive_money"=> [
                "value"=>'UF_CRM_1629103354670',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629103354670",
                "listLabel"=> "Fees paid by users",
                "formLabel"=> "Fees paid by users",
                "filterLabel"=> "Fees paid by users"
            ],
            "is_receive_money"=> [
                "value"=>'UF_CRM_1629103387129',
                "type"=> "boolean",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629103387129",
                "listLabel"=> "Does the user pay?",
                "formLabel"=> "Does the user pay?",
                "filterLabel"=> "Does the user pay?"
            ],
            "order_id"=> [
                "value"=>'UF_CRM_1629192007',
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629192007",
                "listLabel"=> "ORDER_ID",
                "formLabel"=> "ORDER_ID",
                "filterLabel"=> "ORDER_ID"
            ],
            "total_price_paid"=> [
                "value"=>'UF_CRM_1629271456064',
                "type"=> "money",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629271456064",
                "listLabel"=> "Total price Paid",
                "formLabel"=> "Total price Paid",
                "filterLabel"=> "Total price Paid"
            ],
            "platform_type"=> [
                "value"=>'UF_CRM_1629274022921',
                "type"=> "enumeration",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "items"=> [
                    [
                        "ID"=> "45",
                        "VALUE"=> "Call"
                    ],
                    [
                        "ID"=> "47",
                        "VALUE"=> "Android"
                    ],
                    [
                        "ID"=> "49",
                        "VALUE"=> "Android_lite"
                    ],
                    [
                        "ID"=> "51",
                        "VALUE"=> "IOS"
                    ],
                    [
                        "ID"=> "53",
                        "VALUE"=> "Web"
                    ],
                    [
                        "ID"=> "55",
                        "VALUE"=> "Telegram Bot"
                    ]
                ],
                "title"=> "UF_CRM_1629274022921",
                "listLabel"=> "Platform type",
                "formLabel"=> "Platform type",
                "filterLabel"=> "Platform type"
            ],
            "restaurant"=> [
                "value"=>'UF_CRM_1629461733965',
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629461733965",
                "listLabel"=> "Restaurant",
                "formLabel"=> "Restaurant",
                "filterLabel"=> "Restaurant"
            ],
            "is_new"=> [
                "value"=>'UF_CRM_1629539415',
                "type"=> "boolean",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629539415",
                "listLabel"=> "IS NEW",
                "formLabel"=> "IS NEW",
                "filterLabel"=> "IS NEW"
            ],
            "is_update"=> [
                "value"=>'UF_CRM_1629555411',
                "type"=> "boolean",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629555411",
                "listLabel"=> "IS UPDATE",
                "formLabel"=> "IS UPDATE",
                "filterLabel"=> "IS UPDATE"
            ],
            "is_field_update"=> [
                "value"=>'UF_CRM_1629593448',
                "type"=> "boolean",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> true,
                "title"=> "UF_CRM_1629593448",
                "listLabel"=> "IS FIELD UPDATE",
                "formLabel"=> "IS FIELD UPDATE",
                "filterLabel"=> "IS FIELD UPDATE"
            ]
        ],
        'product_section'=>[
            "ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "ID"
            ],
            "CATALOG_ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> true,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Catalog"
            ],
            "SECTION_ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Section"
            ],
            "NAME"=> [
                "type"=> "string",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Name"
            ],
            "XML_ID"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "External ID"
            ],
            "CODE"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Mnemonic code"
            ]
        ],
        'deal_product'=>[
            "ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> true,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "ID"
            ],
            "OWNER_ID"=> [
                "type"=> "integer",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Owner ID"
            ],
            "OWNER_TYPE"=> [
                "type"=> "string",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Owner type"
            ],
            "PRODUCT_ID"=> [
                "type"=> "integer",
                "isRequired"=> true,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Product"
            ],
            "PRODUCT_NAME"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Product name"
            ],
            "PRICE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Price"
            ],
            "PRICE_EXCLUSIVE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Discounted price without tax"
            ],
            "PRICE_NETTO"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "PRICE_NETTO"
            ],
            "PRICE_BRUTTO"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "PRICE_BRUTTO"
            ],
            "QUANTITY"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Quantity"
            ],
            "DISCOUNT_TYPE_ID"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Discount type"
            ],
            "DISCOUNT_RATE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Discount value"
            ],
            "DISCOUNT_SUM"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Discount amount"
            ],
            "TAX_RATE"=> [
                "type"=> "double",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Tax"
            ],
            "TAX_INCLUDED"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Tax included"
            ],
            "CUSTOMIZED"=> [
                "type"=> "char",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Modified on"
            ],
            "MEASURE_CODE"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Unit of measurement code"
            ],
            "MEASURE_NAME"=> [
                "type"=> "string",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Unit of measurement"
            ],
            "SORT"=> [
                "type"=> "integer",
                "isRequired"=> false,
                "isReadOnly"=> false,
                "isImmutable"=> false,
                "isMultiple"=> false,
                "isDynamic"=> false,
                "title"=> "Sort"
            ]
        ]
    ]
];