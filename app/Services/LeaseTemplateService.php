<?php

namespace App\Services;

use Carbon\Carbon;

class LeaseTemplateService
{
    /**
     * Build a province-specific PreLease Canada lease agreement.
     *
     * @param array $data {
     *     province: string, property: Property, renter: User, landlord: User,
     *     leaseType: string, monthlyRent: float, startDate: Carbon, endDate: Carbon,
     *     specialConditions: ?string, totalRent: float, supportFee: float,
     *     commissionFee: float, insuranceFee: float, totalPayable: float,
     *     paymentPlan: ?string
     * }
     */
    public function build(array $data): string
    {
        $province = $this->normalizeProvince($data['province'] ?? 'Ontario');

        return match ($province) {
            'quebec'           => $this->buildQuebec($data),
            'alberta'          => $this->buildAlberta($data),
            'british columbia' => $this->buildBritishColumbia($data),
            'ontario'          => $this->buildOntario($data),
            default            => $this->buildDefault($data),
        };
    }

    public function getProvinceMeta(string $province): array
    {
        $province = $this->normalizeProvince($province);
        return [
            'ontario' => [
                'name' => 'Ontario',
                'act' => 'Residential Tenancies Act, 2006 (Ontario)',
                'authority' => 'Landlord and Tenant Board (LTB)',
                'authority_url' => 'tribunalsontario.ca/ltb',
            ],
            'quebec' => [
                'name' => 'Quebec',
                'act' => 'Civil Code of Quebec — Book Five, Title Two (Lease of Dwellings)',
                'authority' => 'Tribunal administratif du logement (TAL)',
                'authority_url' => 'tal.gouv.qc.ca',
            ],
            'alberta' => [
                'name' => 'Alberta',
                'act' => 'Residential Tenancies Act (Alberta), R.S.A. 2000, c. R-17.1',
                'authority' => 'Residential Tenancy Dispute Resolution Service (RTDRS)',
                'authority_url' => 'rtdrs.alberta.ca',
            ],
            'british columbia' => [
                'name' => 'British Columbia',
                'act' => 'Residential Tenancy Act (British Columbia)',
                'authority' => 'Residential Tenancy Branch (RTB)',
                'authority_url' => 'gov.bc.ca/rtb',
            ],
        ][$province] ?? [
            'name' => ucfirst($province),
            'act' => 'Applicable Provincial Residential Tenancy Legislation',
            'authority' => 'Provincial Tenancy Authority',
            'authority_url' => '',
        ];
    }

    private function normalizeProvince(string $province): string
    {
        $p = strtolower(trim($province));
        $aliases = [
            'on' => 'ontario', 'ont' => 'ontario',
            'qc' => 'quebec', 'que' => 'quebec', 'québec' => 'quebec',
            'ab' => 'alberta', 'alta' => 'alberta',
            'bc' => 'british columbia', 'b.c.' => 'british columbia',
        ];
        if (isset($aliases[$p])) return $aliases[$p];
        foreach (['ontario', 'quebec', 'alberta', 'british columbia'] as $known) {
            if (str_contains($p, $known)) return $known;
        }
        return $p;
    }

    private function buildOntario(array $d): string
    {
        $header = $this->headerBlock('Ontario (Toronto / Ottawa)', $this->getProvinceMeta('ontario'));
        $body = $this->commonBody($d);
        $partE2 = "E2. Pets\nUnder the Ontario Residential Tenancies Act, 2006 (s.14), no-pet clauses in residential leases are VOID and unenforceable. The Tenant may keep pets unless they cause damage or disturbance.\n\n";
        $partF = "PART F — ENDING THE TENANCY\nOntario — Residential Tenancies Act, 2006\n"
            . "- Fixed-term: At end of term, tenancy automatically converts to month-to-month unless renewed.\n"
            . "- Tenant may terminate month-to-month tenancy with 60 days written notice (Form N9).\n"
            . "- Landlord termination is restricted to specific grounds and requires LTB-approved notice forms.\n"
            . "- Non-payment of rent: 14-day notice (Form N4); Tenant can void notice by paying within 14 days.\n"
            . "- Disputes: Landlord and Tenant Board (LTB) — tribunalsontario.ca/ltb\n\n";
        $appendix = $this->ontarioAppendix();
        return $header . $body['parties'] . $body['unit'] . $body['term_rent'] . $body['rights'] . $body['additional_pre'] . $partE2 . $body['additional_post'] . $partF . $this->preleaseAddendum('ontario') . $this->signaturesBlock() . $appendix;
    }

    private function buildQuebec(array $d): string
    {
        $header = $this->headerBlock('Quebec (Montréal / Québec City)', $this->getProvinceMeta('quebec'));
        $body = $this->commonBody($d, depositRule: "Quebec: NO security deposit is permitted under Quebec law (C.c.Q.). Only the first month's rent may be collected at signing.");
        $partE2 = "E2. Pets\nThe Landlord may oppose pets only if they cause nuisance or damage. A blanket no-pet clause may be challenged at the Tribunal administratif du logement (TAL).\n\n";
        $partF = "PART F — ENDING THE TENANCY\nQuebec — Civil Code of Quebec (Arts. 1877–1978)\n"
            . "- Lease of 12 months or more: Tenant must give 3 to 6 months notice before end of term to NOT renew.\n"
            . "- Lease of 6 months or less: 1 month notice before end of term.\n"
            . "- Lease renews automatically at end of term unless proper notice is given.\n"
            . "- Landlord may not evict for personal use without proper notice under Art. 1960 C.c.Q.\n"
            . "- Disputes: Tribunal administratif du logement (TAL) — tal.gouv.qc.ca\n\n";
        $appendix = $this->quebecAppendix();
        return $header . $body['parties'] . $body['unit'] . $body['term_rent'] . $body['rights'] . $body['additional_pre'] . $partE2 . $body['additional_post'] . $partF . $this->preleaseAddendum('quebec') . $this->signaturesBlock() . $appendix;
    }

    private function buildAlberta(array $d): string
    {
        $header = $this->headerBlock('Alberta (Edmonton / Calgary)', $this->getProvinceMeta('alberta'));
        $body = $this->commonBody($d, depositRule: "Alberta: Security deposit must not exceed one month's rent. A separate pet damage deposit is permitted.");
        $partE2 = "E2. Pets\nIn Alberta, no-pet clauses are enforceable if agreed in writing. A pet damage deposit may be required.\n\n";
        $partF = "PART F — ENDING THE TENANCY\nAlberta — Residential Tenancies Act\n"
            . "- Fixed-term: Ends automatically on stated end date. No notice required.\n"
            . "- Monthly periodic: Either party gives 1 month written notice.\n"
            . "- Non-payment: Landlord may give 14-day notice; if rent paid within 14 days, notice is void.\n"
            . "- Substantial breach: Either party may give 14-day termination notice.\n"
            . "- Disputes: Residential Tenancy Dispute Resolution Service (RTDRS) — rtdrs.alberta.ca\n\n";
        $appendix = $this->albertaAppendix();
        return $header . $body['parties'] . $body['unit'] . $body['term_rent'] . $body['rights'] . $body['additional_pre'] . $partE2 . $body['additional_post'] . $partF . $this->preleaseAddendum('alberta') . $this->signaturesBlock() . $appendix;
    }

    private function buildBritishColumbia(array $d): string
    {
        $header = $this->headerBlock('British Columbia (Vancouver / Victoria)', $this->getProvinceMeta('british columbia'));
        $body = $this->commonBody($d, depositRule: "British Columbia: Security deposit must not exceed one-half (½) month's rent (s.17 RTA BC). Pet damage deposit must not exceed one-half month's rent (s.19 RTA BC).");
        $partE2 = "E2. Pets\nIn British Columbia, no-pet clauses are generally enforceable if agreed in writing at start of tenancy. A pet damage deposit (max ½ month's rent) may be required.\n\n";
        $partF = "PART F — ENDING THE TENANCY\nBritish Columbia — Residential Tenancy Act\n"
            . "- Fixed-term: At end of term, tenancy continues month-to-month unless a vacate clause is included.\n"
            . "- Tenant termination of month-to-month: 1 month written notice.\n"
            . "- Landlord ending for landlord's use: 3 months notice (RTB-32L via web portal, effective July 18, 2024).\n"
            . "- Non-payment: 10-day notice (Form RTB-30); Tenant can void by paying within 5 days.\n"
            . "- Disputes: Residential Tenancy Branch (RTB) — gov.bc.ca/rtb\n\n";
        $appendix = $this->britishColumbiaAppendix();
        return $header . $body['parties'] . $body['unit'] . $body['term_rent'] . $body['rights'] . $body['additional_pre'] . $partE2 . $body['additional_post'] . $partF . $this->preleaseAddendum('british columbia') . $this->signaturesBlock() . $appendix;
    }

    private function buildDefault(array $d): string
    {
        $meta = $this->getProvinceMeta($d['province'] ?? '');
        $header = $this->headerBlock($meta['name'], $meta);
        $body = $this->commonBody($d);
        $partE2 = "E2. Pets\nPet policies are subject to applicable provincial law. Refer to your province's residential tenancy authority.\n\n";
        $partF = "PART F — ENDING THE TENANCY\nThis lease is governed by the applicable provincial residential tenancy legislation. Both parties are encouraged to consult the relevant provincial tenancy authority for specific notice requirements and procedures.\n\n";
        $appendix = "APPENDIX — PROVINCIAL LEGAL NOTES\nGoverning Legislation: {$meta['act']}\nDispute Resolution: {$meta['authority']}\nThis PreLease Canada agreement is designed to comply with applicable Canadian residential tenancy law. Both parties are encouraged to consult a licensed lawyer before signing.\n";
        return $header . $body['parties'] . $body['unit'] . $body['term_rent'] . $body['rights'] . $body['additional_pre'] . $partE2 . $body['additional_post'] . $partF . $this->preleaseAddendum('default') . $this->signaturesBlock() . $appendix;
    }

    private function headerBlock(string $provinceLabel, array $meta): string
    {
        $h = "═══════════════════════════════════════════════════════════════════════\n";
        $h .= "                       PRELEASE CANADA\n";
        $h .= "                RESIDENTIAL TENANCY AGREEMENT\n";
        $h .= "                       {$provinceLabel}\n";
        $h .= "═══════════════════════════════════════════════════════════════════════\n\n";
        $h .= "Governed by: {$meta['act']}\n";
        $h .= "Disputes: {$meta['authority']}" . ($meta['authority_url'] ? " — {$meta['authority_url']}" : '') . "\n\n";
        $h .= "INCLUDING PRELEASE CANADA THIRD-PARTY GUARANTOR ADDENDUM\n";
        $h .= "www.preleasecanada.ca\n\n";
        $h .= "──────────────────────────  IMPORTANT LEGAL NOTICE  ──────────────────────────\n";
        $h .= "DRAFT TEMPLATE — FOR REVIEW PURPOSES ONLY. This document is a residential\n";
        $h .= "tenancy agreement template prepared by PreLease Canada Inc. It is not a\n";
        $h .= "substitute for independent legal advice. All parties are strongly advised to\n";
        $h .= "consult a licensed lawyer or paralegal in the applicable province before\n";
        $h .= "signing. PreLease Canada makes no representations or warranties regarding\n";
        $h .= "the legal sufficiency of this template. Verify current legislation before use.\n";
        $h .= "─────────────────────────────────────────────────────────────────────────\n\n";
        $h .= "Date of Agreement: " . now()->format('F d, Y') . "\n\n";
        return $h;
    }

    private function commonBody(array $d, ?string $depositRule = null): array
    {
        $renter = $d['renter'];
        $landlord = $d['landlord'];
        $property = $d['property'];
        $startDate = $d['startDate'] instanceof Carbon ? $d['startDate'] : Carbon::parse($d['startDate']);
        $endDate = $d['endDate'] instanceof Carbon ? $d['endDate'] : Carbon::parse($d['endDate']);
        $months = $startDate->diffInMonths($endDate) ?: ($d['leaseType'] === '3_month' ? 3 : 6);

        $parties = "PART A — PARTIES TO THIS AGREEMENT\n\n";
        $parties .= "A1. Landlord\n";
        $parties .= "  Full Legal Name: " . trim(($landlord->first_name ?? '') . ' ' . ($landlord->last_name ?? '')) . "\n";
        $parties .= "  Email: " . ($landlord->email ?? '') . "\n";
        $parties .= "  Phone: " . ($landlord->phone ?? '____________________') . "\n\n";
        $parties .= "A2. Tenant\n";
        $parties .= "  Full Legal Name: " . trim(($renter->first_name ?? '') . ' ' . ($renter->last_name ?? '')) . "\n";
        $parties .= "  Email: " . ($renter->email ?? '') . "\n";
        $parties .= "  Phone: " . ($renter->phone ?? '____________________') . "\n\n";
        $parties .= "A3. PreLease Canada — Third-Party Guarantor\n";
        $parties .= "  Company: PreLease Canada Inc.\n";
        $parties .= "  Role:    Third-Party Guarantor & Platform Facilitator\n";
        $parties .= "  Website: www.preleasecanada.ca\n";
        $parties .= "  Email:   info@preleasecanada.ca\n\n";

        $unit = "PART B — RENTAL UNIT\n\n";
        $unit .= "  Address:       " . ($property->address ?? '') . "\n";
        $unit .= "  City:          " . ($property->city ?? '') . "\n";
        $unit .= "  Province:      " . ($property->state ?? '') . "\n";
        $unit .= "  Postal Code:   " . ($property->zip_code ?? $property->postal_code ?? '') . "\n";
        $unit .= "  Property Type: " . ($property->property_type ?? 'Residential') . "\n";
        $unit .= "  Bedrooms:      " . ($property->no_of_bedrooms ?? 'N/A') . "  |  Bathrooms: " . ($property->no_of_bathrooms ?? 'N/A') . "\n";
        $unit .= "  Square Feet:   " . ($property->square_feet ?? $property->size ?? 'N/A') . "\n\n";
        $unit .= "B1. Utilities & Services Included in Rent\n";
        $unit .= "  Refer to property listing on PreLease Canada platform for the complete list of included utilities (heat, water, electricity, internet, etc.). Tenant is responsible for any utilities NOT listed.\n\n";

        $monthlyRent = number_format((float) $d['monthlyRent'], 2);
        $totalRent = number_format((float) $d['totalRent'], 2);
        $supportFee = number_format((float) $d['supportFee'], 2);
        $commission = number_format((float) $d['commissionFee'], 2);
        $insurance = number_format((float) $d['insuranceFee'], 2);
        $totalPayable = number_format((float) $d['totalPayable'], 2);
        $paymentPlan = $d['paymentPlan'] ?? 'upfront';

        $term_rent = "PART C — TERM OF TENANCY & RENT\n\n";
        $term_rent .= "C1. Term\n";
        $term_rent .= "  Start Date:   " . $startDate->format('F d, Y') . "\n";
        $term_rent .= "  End Date:     " . $endDate->format('F d, Y') . "\n";
        $term_rent .= "  Duration:     " . str_replace('_', '-', $d['leaseType'] ?? '') . " ({$months} months)\n\n";
        $term_rent .= "C2. Rent\n";
        $term_rent .= "  Monthly Rent (CAD):                   \$ {$monthlyRent}\n";
        $term_rent .= "  Total Rent ({$months} months):                \$ {$totalRent}\n";
        $term_rent .= "  Support Fee (\$100/month):             \$ {$supportFee}\n";
        $term_rent .= "  Platform Commission (5%):             \$ {$commission}\n";
        $term_rent .= "  Insurance Fee:                        \$ {$insurance}\n";
        $term_rent .= "  ────────────────────────────────────────────────\n";
        $term_rent .= "  TOTAL PAYABLE (CAD):                  \$ {$totalPayable}\n\n";
        $term_rent .= "  Rent Due Date: 1st of each month\n";
        $term_rent .= "  Payment Method: " . ucfirst($paymentPlan) . " via PreLease Canada platform\n\n";
        $term_rent .= "C3. PreLease Upfront Payment Option\n";
        $term_rent .= "  PreLease Canada offers Tenants the option to pay rent upfront in advance.\n";
        $term_rent .= "  Upfront amounts are held in trust by PreLease Canada and released to the\n";
        $term_rent .= "  Landlord on the 1st of each rental month.\n\n";
        if ($depositRule) {
            $term_rent .= "C4. Rent Deposit\n  {$depositRule}\n\n";
        }

        $rights = "PART D — RIGHTS & RESPONSIBILITIES\n\n";
        $rights .= "D1. Landlord Obligations\n";
        $rights .= "  - Maintain the rental unit in a good state of repair, compliant with health, safety and housing standards.\n";
        $rights .= "  - Ensure all vital services (heat, water, electricity) are maintained.\n";
        $rights .= "  - Provide the Tenant with a signed copy of this agreement within 21 days of signing.\n";
        $rights .= "  - Give proper written notice (24 hours minimum) before entering the rental unit.\n";
        $rights .= "  - Not harass, interfere with, or discriminate against the Tenant.\n";
        $rights .= "  - Comply with all applicable provincial human rights codes.\n\n";
        $rights .= "D2. Tenant Obligations\n";
        $rights .= "  - Pay rent in full on the due date each month.\n";
        $rights .= "  - Keep the rental unit in a reasonable state of cleanliness.\n";
        $rights .= "  - Not cause damage beyond ordinary wear and tear.\n";
        $rights .= "  - Not disturb other tenants or neighbours.\n";
        $rights .= "  - Maintain valid tenant insurance for the duration of the tenancy.\n";
        $rights .= "  - Notify the Landlord promptly of any required repairs.\n";
        $rights .= "  - Not sublet or assign the unit without the Landlord's written consent.\n\n";
        $rights .= "D3. Entry by Landlord\n";
        $rights .= "  The Landlord must provide the Tenant with at least 24 hours written notice before entering the rental unit, except in case of emergency. Entry must occur between 8:00 AM and 8:00 PM unless the Tenant agrees otherwise.\n\n";
        $rights .= "D4. Repairs & Maintenance\n";
        $rights .= "  The Landlord is responsible for ordinary wear and tear repairs. The Tenant is responsible for damage caused by the Tenant, their guests, or their pets beyond ordinary wear and tear.\n\n";

        $additional_pre = "PART E — ADDITIONAL TERMS\n\n";
        $additional_pre .= "E1. Smoking\n  Smoking is prohibited in the unit and on shared property unless expressly authorized in writing by the Landlord and consistent with municipal/provincial smoking laws.\n\n";

        $additional_post = "E3. Insurance (MANDATORY — PreLease Canada Requirement)\n";
        $additional_post .= "  The Tenant is required to maintain valid tenant/renter's liability insurance for the full duration of this tenancy. Minimum liability coverage: \$1,000,000 CAD.\n";
        $additional_post .= "  Proof of insurance must be provided to the Landlord and to PreLease Canada prior to the tenancy start date and upon annual renewal.\n";
        $additional_post .= "  Insurance is arranged through PreLease Canada's insurance partner and may be purchased directly through the PreLease Canada platform at the time of lease signing.\n\n";
        $additional_post .= "E4. Quiet Enjoyment\n  The Tenant has the right to reasonable quiet enjoyment of the rental unit. The Landlord agrees not to interfere with the Tenant's use and enjoyment of the unit.\n\n";
        $additional_post .= "E5. Guests & Occupants\n  Only the person(s) listed in Part A may occupy the rental unit as their primary residence. Guests may stay for reasonable periods. The Tenant is responsible for the conduct of all guests.\n\n";
        $additional_post .= "E6. Alterations\n  The Tenant may make minor cosmetic changes without prior approval. Any structural or permanent alterations require the Landlord's prior written consent. Upon vacating, the Tenant must restore the unit to its original condition unless the Landlord agrees otherwise in writing.\n\n";

        if (!empty($d['specialConditions'])) {
            $additional_post .= "E7. Special Conditions Agreed Between the Parties\n  " . $d['specialConditions'] . "\n\n";
        }

        return [
            'parties' => $parties,
            'unit' => $unit,
            'term_rent' => $term_rent,
            'rights' => $rights,
            'additional_pre' => $additional_pre,
            'additional_post' => $additional_post,
        ];
    }

    private function preleaseAddendum(string $province): string
    {
        $cityForArbitration = match ($province) {
            'quebec' => 'Montréal, Quebec under Quebec arbitration law',
            'alberta' => 'Edmonton, Alberta under the Arbitration Act (Alberta)',
            'british columbia' => 'Vancouver, British Columbia under the Arbitration Act (BC)',
            'ontario' => 'Toronto, Ontario under the Arbitration Act, 1991 (Ontario)',
            default => 'the province governing this lease, under applicable provincial arbitration law',
        };
        $authority = match ($province) {
            'quebec' => 'Tribunal administratif du logement (TAL)',
            'alberta' => 'Alberta Residential Tenancy Dispute Resolution Service (RTDRS)',
            'british columbia' => 'BC Residential Tenancy Branch (RTB)',
            'ontario' => 'Ontario Landlord and Tenant Board (LTB)',
            default => 'applicable provincial tenancy authority',
        };

        $a = "PART G — PRELEASE CANADA THIRD-PARTY GUARANTOR ADDENDUM\n\n";
        $a .= "This Addendum forms an integral part of the Residential Tenancy Agreement to which it is attached. In the event of any conflict between this Addendum and any other term of the Agreement, this Addendum shall prevail with respect to matters concerning PreLease Canada's role as guarantor.\n\n";
        $a .= "G1. Nature of Guarantee\n";
        $a .= "  PreLease Canada Inc. (\"PreLease Canada\" or the \"Guarantor\") provides a limited third-party guarantee on behalf of the Tenant. PreLease Canada's guarantee is conditional and limited. It does NOT constitute an unconditional surety bond.\n\n";
        $a .= "G2. Scope of Guarantee\n";
        $a .= "  PreLease Canada guarantees, subject to G3 below:\n";
        $a .= "  - That the Tenant's identity has been verified by the PreLease Canada platform.\n";
        $a .= "  - That the rental application was reviewed and approved by PreLease Canada.\n";
        $a .= "  - That tenant insurance has been confirmed or arranged in accordance with E3.\n";
        $a .= "  - In case of Tenant default within the first months of tenancy, PreLease Canada will make commercially reasonable efforts to assist the Landlord in recovering unpaid rent up to the agreed Guarantee Cap.\n\n";
        $a .= "G3. Conditions of Guarantee\n";
        $a .= "  - The Landlord has provided the Tenant with a signed copy of this agreement within 21 days.\n";
        $a .= "  - The Tenant's default is not caused by the Landlord's breach of this agreement.\n";
        $a .= "  - The Landlord has notified PreLease Canada in writing within 10 business days of default.\n";
        $a .= "  - The Landlord has not accepted substitute rent from a third party or unauthorized occupant.\n";
        $a .= "  - The tenancy agreement was executed through the PreLease Canada platform.\n\n";
        $a .= "G4. What PreLease Canada is NOT Guaranteeing\n";
        $a .= "  PreLease Canada does NOT:\n";
        $a .= "  - Guarantee payment of utilities, taxes, insurance premiums, or other charges.\n";
        $a .= "  - Guarantee the Tenant's behaviour, compliance, or condition of the unit.\n";
        $a .= "  - Assume or take over the tenancy or sublease the unit.\n";
        $a .= "  - Guarantee payment of rent beyond the agreed Guarantee Cap.\n";
        $a .= "  - Act as co-tenant or co-signor.\n";
        $a .= "  - Provide legal representation in any dispute proceeding.\n\n";
        $a .= "G5. Insurance Facilitation\n";
        $a .= "  PreLease Canada has facilitated the arrangement of tenant insurance through its insurance partner on behalf of the Tenant. PreLease Canada does not issue, underwrite, or administer the insurance policy itself.\n\n";
        $a .= "G6. PreLease Canada Platform Obligations\n";
        $a .= "  - Verified the Tenant's identity through its platform.\n";
        $a .= "  - Collected and transmitted upfront rent (if applicable) to the Landlord.\n";
        $a .= "  - Confirmed proof of tenant insurance prior to lease execution.\n";
        $a .= "  - Maintained records of this tenancy agreement within its secure platform.\n\n";
        $a .= "G7. Limitation of Liability\n";
        $a .= "  PreLease Canada's total aggregate liability shall not exceed the lesser of:\n";
        $a .= "  (a) the agreed Guarantee Cap; or (b) three months' rent as stated in Part C.\n";
        $a .= "  PreLease Canada shall not be liable for indirect, consequential, special, or punitive damages.\n\n";
        $a .= "G8. Governing Law & Dispute Resolution\n";
        $a .= "  This Addendum is governed by the laws of the province stated in Part B. Disputes regarding the tenancy shall be referred to the {$authority}. Disputes regarding PreLease Canada's obligations shall be subject to arbitration in {$cityForArbitration}.\n\n";
        $a .= "G9. Severability\n  If any provision is invalid under applicable law, that provision shall be severed and the remaining provisions continue in full force and effect.\n\n";
        $a .= "G10. Acknowledgement\n  All parties acknowledge that they have read, understood, and agreed to the terms of this Guarantor Addendum, and have had the opportunity to seek independent legal advice.\n\n";
        return $a;
    }

    private function signaturesBlock(): string
    {
        $s = "─── SIGNATURES ────────────────────────────────────────────────────────────\n\n";
        $s .= "LANDLORD\n";
        $s .= "  Signature: _________________________________   Date: _______________\n";
        $s .= "  Name:      _________________________________   Title: ______________\n\n";
        $s .= "TENANT 1\n";
        $s .= "  Signature: _________________________________   Date: _______________\n";
        $s .= "  Name:      _________________________________   ID: _________________\n\n";
        $s .= "TENANT 2 (if applicable)\n";
        $s .= "  Signature: _________________________________   Date: _______________\n";
        $s .= "  Name:      _________________________________   ID: _________________\n\n";
        $s .= "PRELEASE CANADA INC. — AUTHORIZED REPRESENTATIVE\n";
        $s .= "  Signature: _________________________________   Date: _______________\n";
        $s .= "  Name:      _________________________________   Title: ______________\n";
        $s .= "  Guarantee Cap Agreed: CAD \$________________\n\n";
        $s .= "Both parties agree that electronic signatures via the PreLease Canada platform constitute valid and binding signatures under Canadian federal and provincial electronic commerce legislation.\n\n";
        $s .= "Certificate of Service\n";
        $s .= "  The Landlord certifies that a signed copy of this agreement was provided to the Tenant on:\n";
        $s .= "  Date Delivered: ____________________________________________________\n";
        $s .= "  Method (email / in person / registered mail): _____________________\n\n";
        return $s;
    }

    private function ontarioAppendix(): string
    {
        return "─── APPENDIX — ONTARIO PROVINCIAL LEGAL NOTES ────────────────────────────\n"
            . "Governing Legislation: Residential Tenancies Act, 2006 (Ontario)\n"
            . "Dispute Resolution: Landlord and Tenant Board (LTB) — tribunalsontario.ca/ltb\n\n"
            . "- Ontario requires a mandatory Standard Form of Lease for most residential tenancies signed after April 30, 2018 (updated December 2020). The Landlord MUST provide the Tenant with the Government of Ontario's Standard Form of Lease. This agreement supplements that form.\n"
            . "- Only a last month's rent deposit is permitted. NO damage deposits allowed (s.105, RTA, 2006).\n"
            . "- Rent increases are subject to the annual rent increase guideline set by the Province of Ontario.\n"
            . "- Tenants have the right to a hearing before the LTB prior to eviction. Self-help evictions are illegal.\n"
            . "- A no-pets clause is VOID and unenforceable in Ontario (s.14, RTA).\n"
            . "- Under the Ontario Human Rights Code, the Landlord must not discriminate on grounds including race, ancestry, place of origin, colour, ethnic origin, citizenship, or immigration status.\n"
            . "- Tenants have the right to quiet enjoyment of the premises (s.22, RTA).\n"
            . "- PreLease Canada Note: Both Toronto and Ottawa tenancies are governed by Ontario law and use the same Standard Form of Lease.\n\n";
    }

    private function quebecAppendix(): string
    {
        return "─── APPENDIX — QUEBEC PROVINCIAL LEGAL NOTES ─────────────────────────────\n"
            . "Governing Legislation: Civil Code of Quebec — Book Five, Title Two (Lease of Dwellings)\n"
            . "Dispute Resolution: Tribunal administratif du logement (TAL) — tal.gouv.qc.ca\n\n"
            . "- IMPORTANT: In Quebec, the mandatory lease form from the TAL MUST be used for all residential tenancies. This PreLease Canada agreement SUPPLEMENTS that form but does not replace it.\n"
            . "- The official Quebec bail (lease form) is updated periodically. Ensure you use the current version.\n"
            . "- NO security deposit is permitted in Quebec. Only the first month's rent may be collected at signing.\n"
            . "- The lease must be written in French. The parties may agree to use another language at the request of the tenant.\n"
            . "- Quebec leases renew automatically at end of term unless proper notice is given.\n"
            . "- The Landlord must disclose the lowest rent paid in the last 12 months (Section G of the TAL bail).\n"
            . "- Rent increases are regulated by the Civil Code of Quebec.\n"
            . "- Tenants have the right of first refusal if the Landlord sells the building.\n"
            . "- Evictions are handled exclusively through the Tribunal administratif du logement.\n"
            . "- For leases signed after February 21, 2024, the landlord must disclose the maximum rent for the first 5 years (new buildings only).\n"
            . "- PreLease Canada Note: Both Montréal and Québec City are governed by the same provincial law.\n\n";
    }

    private function albertaAppendix(): string
    {
        return "─── APPENDIX — ALBERTA PROVINCIAL LEGAL NOTES ────────────────────────────\n"
            . "Governing Legislation: Residential Tenancies Act (Alberta)\n"
            . "Dispute Resolution: Residential Tenancy Dispute Resolution Service (RTDRS) — rtdrs.alberta.ca\n\n"
            . "- Alberta does not require a specific mandatory lease form. This PreLease Canada agreement is designed to comply with the Alberta Residential Tenancies Act.\n"
            . "- Security deposit must not exceed one month's rent. A separate pet damage deposit is permitted.\n"
            . "- The Landlord must complete a move-in inspection report within one week of the Tenant moving in.\n"
            . "- The Landlord must provide the Tenant with a copy of the signed agreement within 21 days.\n"
            . "- Fixed-term tenancy: Ends automatically on the stated end date. No notice required.\n"
            . "- Monthly periodic tenancy: 1 month written notice to end.\n"
            . "- Non-payment of rent: Landlord may give 14-day notice to terminate. If rent is paid within 14 days, the notice is void.\n"
            . "- Landlord may enter the rental unit with 24 hours written notice between 8:00 AM and 8:00 PM.\n"
            . "- No-pets clauses are enforceable in Alberta if agreed in writing.\n"
            . "- Disputes are resolved at the RTDRS or Provincial Court. RTDRS decisions are enforceable as court orders.\n"
            . "- PreLease Canada Note: Service Alberta provides free landlord and tenant information at 1-877-427-4088.\n\n";
    }

    private function britishColumbiaAppendix(): string
    {
        return "─── APPENDIX — BRITISH COLUMBIA PROVINCIAL LEGAL NOTES ───────────────────\n"
            . "Governing Legislation: Residential Tenancy Act (BC)\n"
            . "Dispute Resolution: Residential Tenancy Branch (RTB) — gov.bc.ca/rtb\n\n"
            . "- British Columbia uses the RTB-1 Standard Residential Tenancy Agreement. This PreLease Canada agreement supplements the RTB-1 form.\n"
            . "- Security deposit must not exceed one-half month's rent (s.17, RTA BC).\n"
            . "- Pet damage deposit must not exceed one-half month's rent (s.19, RTA BC).\n"
            . "- Landlord must conduct a move-in condition inspection within the first month, and a move-out inspection at end of tenancy.\n"
            . "- Landlord must give at least 24 hours written notice before entering a rental unit.\n"
            . "- Fixed-term tenancies: At end of term, if a vacate clause is not included, the tenancy continues month-to-month.\n"
            . "- Landlord may end tenancy for landlord's use with 3 months notice (form RTB-32L via web portal, effective July 18, 2024).\n"
            . "- Rent increases are subject to the annual BC rent increase guideline. Notice must be given using form RTB-7.\n"
            . "- No-pets clauses are generally enforceable in BC if agreed in writing at start of tenancy.\n"
            . "- Disputes are resolved by the Residential Tenancy Branch. Self-help remedies are not allowed.\n"
            . "- Electronic signatures are valid for tenancy agreements if both parties consent.\n\n";
    }
}
