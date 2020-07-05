<?php

namespace App\Http\Controllers;

use App\CompanyDetail;
use Goutte\Client;
use App\CompanyList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyListController extends Controller
{

    ##############################Public Functions##############################

    /**Get Individual URL for all the companies */
    public function getCompanyLinks()
    {
        $companies_list = CompanyList::all();
        foreach ($companies_list as $company) {
            $company_url = $company->url;
            $company_data = $this->getcompanydetails($company_url);
        }
        return "Company details stored successfully";
    }

    /**Parsing & getting the company list
     * Request Format
     * {
     * "url":"//http://www.mycorporateinfo.com/industry/section/A"
     * }
     */
    public function webparse(Request $request)
    {
        $client = new Client();

        $url = $request->input('url');

        // create a crawler object from this link
        $crawler = $client->request('GET', $url);

        $pages = $crawler->filter('html body div section div div div div.right ul')->filter('li');
        $page_index = (count($pages) - 2);
        $total_pages = $pages->eq($page_index)->text();
        $total_pages = (int) $total_pages;
        $this->getcompanylist($url);
        // for ($intCounter = 2; $intCounter <= $total_pages; $intCounter++) {
        for ($intCounter = 2; $intCounter <= 30; $intCounter++) {
            $modified_url = $url . '/page/' . $intCounter;
            $this->getcompanylist($modified_url);
        }
        return "Company List Created Successfully";
    }

    ##############################Private Functions##############################

    //**Function to remove the <a> tags */
    private function excludehrefs($value)
    {
        $pos = stripos($value, 'See other companies');
        if ($pos) {
            $value = substr($value, 0, $pos);
            return $value;
        } else {
            return $value;
        }
    }

    /**Formatting of date according to DB */
    private function formatDate($value)
    {
        $value = str_replace("(", "", $value);
        $datestr = str_replace(")", "", $value);
        $datestr = Carbon::parse($datestr)->format('Y-m-d');
        return $datestr;
    }

    /**Parsing and getting all the required details for the given company URL */
    private function getcompanydetails($url)
    {
        $client = new Client();

        // create a crawler object from this link
        $crawler = $client->request('GET', $url);

        $page_detail = $crawler->filter('html body div section div div')->eq(1);
        // ->filter('div.roomy-20');
        $company_information = $page_detail->filter('div.roomy-20 div#companyinformation table tbody');
        $company_cin = $company_information->filter('tr')->filter('td')->eq(1)->text();
        $company_id = CompanyList::where('cin', $company_cin)->get();
        $company_id = $company_id[0]->id;

        $company_details = ([
            'company_id' => $company_id,
            'company_status' => 9,
            'age' => "NA",
            'category' => "NA",
            'sub_category' => "NA",
            'company_class' => "NA",
            'roc_code' => "NA",
            'members_count' => 0,
            'email' => "NA",
            'address' => "NA",
            'is_listed' => 0,
            'state' => "NA",
            'district' => "NA",
            'city' => "NA",
            'pin' => -1,
            'section' => "NA",
            'division' => "NA",
            'main_group' => "NA",
            'main_class' => "NA",
        ]);

        $company_information->filter('tr')->each(function ($node) use (&$company_details) {
            $key = $node->filter('td')->eq(0)->text();
            $value = $node->filter('td')->eq(1)->text();
            $newKey = $this->getCompanyInformationKey($key);
            if ($newKey) {
                if (array_key_exists('post_process', $newKey)) {
                    $value = $newKey['post_process']($value);
                }
                $company_details[$newKey['new_key']] = $value;
            }
        });

        $contact_detail = $page_detail->filter('div.roomy-20 div#contactdetails  table tbody');
        $contact_detail->filter('tr')->each(function ($node) use (&$company_details) {
            $key = $node->filter('td')->eq(0)->text();
            $value = $node->filter('td')->eq(1)->text();
            $newKey = $this->getContactDetailsKey($key);
            if ($newKey) {
                if (array_key_exists('post_process', $newKey)) {
                    $value = $newKey['post_process']($value);
                }
                $company_details[$newKey['new_key']] = $value;
            }
        });

        $list_detail = $page_detail->filter('div#listingandannualcomplaincedetails  table tbody');
        $list_detail->filter('tr')->each(function ($node) use (&$company_details) {
            $key = $node->filter('td')->eq(0)->text();
            $value = $node->filter('td')->eq(1)->text();
            $newKey = $this->getComplianceDetailsKey($key);
            if ($newKey) {
                if (array_key_exists('post_process', $newKey)) {
                    $value = $newKey['post_process']($value);
                }

                $company_details[$newKey['new_key']] = $value;
            }
        });

        $location_detail = $page_detail->filter('div#otherinformation table')->eq(0)->filter('tbody');
        $location_detail->filter('tr')->each(function ($node) use (&$company_details) {
            $key = $node->filter('td')->eq(0)->text();
            $value = $node->filter('td')->eq(1)->filter('a')->text();
            $newKey = $this->getLocationDetailsKey($key);
            if ($newKey) {
                if (array_key_exists('post_process', $newKey)) {
                    $value = $newKey['post_process']($value);
                }
                $company_details[$newKey['new_key']] = $value;
            }
        });

        $industry_detail = $page_detail->filter('div#otherinformation div#industryclassification table tbody');
        $industry_detail->filter('tr')->each(function ($node) use (&$company_details) {
            $key = $node->filter('td')->eq(0)->text();
            $value = $node->filter('td')->eq(1)->text();
            $newKey = $this->getIndustryDetailsKey($key);
            if ($newKey) {
                if (array_key_exists('post_process', $newKey)) {
                    $value = $newKey['post_process']($value);
                }
                $company_details[$newKey['new_key']] = $value;
            }
        });

        $this->storeCompanyDetails($company_details);
    }

    /**Creating Company information key from the DOM */
    private function getCompanyInformationKey($key)
    {
        $keyHash = ([
            'Corporate Identification Number' => ([
                'new_key' => 'cin'
            ]),
            'Registration Number' => ([
                'new_key' => 'registration_number'
            ]),
            'Company Status' => ([
                'new_key' => 'company_status',
                'post_process' => (function ($value) {
                    return $this->getStatusCode($value);
                }),
            ]),
            'Age (Date of Incorporation)' => ([
                'new_key' => 'age',
                'post_process' => (function ($value) {
                    $value = $this->excludehrefs($value);
                    return $this->formatDate($value);
                })

            ]),
            'Company Category' => ([
                'new_key' => 'category',
                'post_process' => (function ($value) {
                    return $this->excludehrefs($value);
                })
            ]),
            'Company Subcategory' => ([
                'new_key' => 'sub_category',
                'post_process' => (function ($value) {
                    return $this->excludehrefs($value);
                })
            ]),
            'Class of Company' => ([
                'new_key' => 'company_class',
                'post_process' => (function ($value) {
                    return $this->excludehrefs($value);
                })
            ]),
            'ROC Code' => ([
                'new_key' => 'roc_code',
                'post_process' => (function ($value) {
                    return $this->excludehrefs($value);
                })
            ]),
            'Number of Members (Applicable only in case of company without Share Capital)' => ([
                'new_key' => 'members_count'
            ]),
        ]);

        return array_key_exists($key, $keyHash) ? $keyHash[$key] : null;
    }

    /**Storing the company list*/
    private function getcompanylist($url)
    {
        $client = new Client();

        // create a crawler object from this link
        $crawler = $client->request('GET', $url);

        $arr_details = [];

        $base_link = config('app.base_link');

        $crawler->filter('html body div section div div div table tbody')->filter('tr')
            ->each(function ($node) use (&$arr_details, $base_link) {
                if (count($node->filter('td')) > 0) {
                    $url =  $node->filter('td')->eq(1)->filter('a')->attr('href');
                    $company_url = $base_link . $url;
                    array_push($arr_details, [
                        "cin" => $node->filter('td')->eq(0)->text(),
                        "name" => $node->filter('td')->eq(1)->text(),
                        "company_url" => $company_url
                    ]);
                }
            });
        $this->storeCompanyList($arr_details);
    }

    /**Creating Compliance information key from the DOM */
    private function getComplianceDetailsKey($key)
    {
        $keyHash = ([
            'Whether listed or not' => ([
                'new_key' => 'is_listed',
                'post_process' => (function ($value) {
                    return Str::startsWith($value, 'Listed') ? 1 : 0;
                })
            ])

        ]);

        return array_key_exists($key, $keyHash) ? $keyHash[$key] : null;
    }

    /**Creating Contact information key from the DOM */
    private function getContactDetailsKey($key)
    {
        $keyHash = ([
            'Email Address' => ([
                'new_key' => 'email'
            ]),
            'Registered Office' => ([
                'new_key' => 'address'
            ])
        ]);

        return array_key_exists($key, $keyHash) ? $keyHash[$key] : null;
    }

    /**Creating Industry information key from the DOM */
    private function getIndustryDetailsKey($key)
    {
        $keyHash = ([
            'Section' => ([
                'new_key' => 'section',
                'post_process' => (function ($value) {
                    return $this->excludehrefs($value);
                })
            ]),
            'Division' => ([
                'new_key' => 'division',
                'post_process' => (function ($value) {
                    return $this->excludehrefs($value);
                })
            ]),
            'Main Group' => ([
                'new_key' => 'main_group',
                'post_process' => (function ($value) {
                    return $this->excludehrefs($value);
                })
            ]),
            'Main Class' => ([
                'new_key' => 'main_class',
                'post_process' => (function ($value) {
                    return $this->excludehrefs($value);
                })
            ])
        ]);

        return array_key_exists($key, $keyHash) ? $keyHash[$key] : null;
    }

    /**Creating Location information key from the DOM */
    private function getLocationDetailsKey($key)
    {
        $keyHash = ([
            'State' => ([
                'new_key' => 'state'
            ]),
            'District' => ([
                'new_key' => 'district'
            ]),
            'City' => ([
                'new_key' => 'city'
            ]),
            'PIN' => ([
                'new_key' => 'pin',
                'post_process' => (function ($value) {
                    return (int) $value;
                })
            ]),
        ]);

        return array_key_exists($key, $keyHash) ? $keyHash[$key] : null;
    }

    /**Setting the company status from the DOM */
    private function getStatusCode($status_value)
    {
        switch ($status_value) {
            case "InActive":
                $status = 0;
                break;
            case "Active":
                $status = 1;
                break;
            case "Strike Off":
                $status = 2;
                break;
            case "Under Liquidation":
                $status = 3;
                break;
            case "Liquidated":
                $status = 4;
                break;
            case "Dissolved":
                $status = 5;
                break;
            case "Amalgamated":
                $status = 6;
                break;
            case "Not available for efiling":
                $status = 7;
                break;
            default:
                $status = 9;
                break;
        }
        return $status;
    }

    /**Storing the Comapany details into Company Details table */
    private function storeCompanyDetails($obj_company_details)
    {
        $company_detail = CompanyDetail::updateorcreate([
            'company_id' => $obj_company_details['company_id'],
            'corporate_identification_number' => $obj_company_details['cin'],
            'registration_number' => $obj_company_details['registration_number']
        ], [
            'company_status' => $obj_company_details['company_status'],
            'age' => $obj_company_details['age'],
            'category' => $obj_company_details['category'],
            'sub_category' => $obj_company_details['sub_category'],
            'company_class' => $obj_company_details['company_class'],
            'roc_code' => $obj_company_details['roc_code'],
            'members_count' => $obj_company_details['members_count'],
            'email' => $obj_company_details['email'],
            'address' => $obj_company_details['address'],
            'is_listed' => $obj_company_details['is_listed'],
            'state' => $obj_company_details['state'],
            'district' => $obj_company_details['district'],
            'city' => $obj_company_details['city'],
            'pin' => $obj_company_details['pin'],
            'section' => $obj_company_details['section'],
            'division' => $obj_company_details['division'],
            'main_group' => $obj_company_details['main_group'],
            'main_class' => $obj_company_details['main_class']
        ]);
    }

    /**Create records in the Company List table in DB */
    private function storeCompanyList($arr_details)
    {
        foreach ($arr_details as $arr_detail) {
            $company_list = CompanyList::updateorcreate([
                'cin' => $arr_detail['cin']
            ], [
                'name' => $arr_detail['name'],
                'url' => $arr_detail['company_url']
            ]);
        }
    }
}
