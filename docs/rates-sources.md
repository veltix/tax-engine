# EU VAT rate table: sources

`EuVatRates` (rate table version `2026.1`) and `Country::STANDARD_RATES` hold the
rates in force on **2026-10-05**. This file records where each rate comes from
and when the 2025–2026 changes took effect.

## How the rates were checked

The primary source is the European Commission's **Taxes in Europe Database
(TEDB)**, read through its public VAT retrieval web service
(`https://ec.europa.eu/taxation_customs/tedb/ws/`, WSDL at
`https://ec.europa.eu/taxation_customs/tedb/ws/VatRetrievalService.wsdl`) for all
27 member states (TEDB uses `EL` for Greece) with `situationOn` set to
2026-10-05, and again for 2024-12-31, 2025-01-01, 2025-07-01, 2025-07-31,
2025-08-01, 2025-12-31, 2026-01-01 and 2026-06-30 to date each change. Every
change was then checked against the national tax authority or legislature.

Only the mainland rates are kept. Regional rates (Azores, Madeira, Corsica,
the French overseas departments, the Canary Islands, the Greek islands,
Jungholz and Mittelberg) and zero rates are not part of the table.

## Rates by country

`reduced` is the lower reduced rate, `reduced_second` the higher one.
A dash means the country has no such rate in the table.

| Country | Standard | reduced | reduced_second | super_reduced | parking | Change in 2025–2026 |
|---|---|---|---|---|---|---|
| AT Austria | 20 | 10 | 13 | **4.9** | 13 | New 4.9 % rate on basic foodstuffs from **2026-07-01** |
| BE Belgium | 21 | 6 | 12 | – | 12 | – |
| BG Bulgaria | 20 | 9 | – | – | – | – |
| HR Croatia | 25 | 5 | 13 | – | – | – |
| CY Cyprus | 19 | 5 | 9 | **3** | – | None; the 3 % rate (since 2023-07-21) was missing from the table |
| CZ Czechia | 21 | 12 | – | – | – | – |
| DK Denmark | 25 | – | – | – | – | – |
| EE Estonia | 24 | 9 | **13** | – | – | 13 % on accommodation from **2025-01-01**; standard 22 → 24 % from 2025-07-01 (already in 1.0.x) |
| FI Finland | 25.5 | 10 | **13.5** | – | – | 14 → 13.5 % from **2026-01-01** |
| FR France | 20 | 5.5 | 10 | 2.1 | – | – |
| DE Germany | 19 | 7 | – | – | – | – |
| GR Greece | 24 | 6 | 13 | – | – | None on the mainland (more islands at −30 % from 2026-01-01) |
| HU Hungary | 27 | 5 | 18 | – | – | – |
| IE Ireland | 23 | 9 | 13.5 | 4.8 | 13.5 | – |
| IT Italy | 22 | 5 | 10 | 4 | – | – |
| LV Latvia | 21 | 5 | 12 | – | – | – |
| LT Lithuania | 21 | 5 | **12** | – | – | 9 → 12 % from **2026-01-01** |
| LU Luxembourg | 17 | 8 | – | 3 | 14 | – |
| MT Malta | 18 | 5 | 7 | – | **12** | None; the 12 % parking rate was missing from the table |
| NL Netherlands | 21 | 9 | – | – | – | – |
| PL Poland | 23 | 5 | 8 | – | – | – |
| PT Portugal | 23 | 6 | 13 | – | 13 | – |
| RO Romania | **21** | **11** | – | – | – | Standard 19 → 21 %, and 5 % and 9 % merged into 11 %, from **2025-08-01** |
| SK Slovakia | 23 | **5** | **19** | – | – | 10 % replaced by 5 % and 19 % from **2025-01-01** (standard 20 → 23 % already in 1.0.x) |
| SI Slovenia | 22 | 5 | 9.5 | – | – | – |
| ES Spain | 21 | 10 | – | 4 | – | – |
| SE Sweden | 25 | 6 | 12 | – | – | – |

Bold values changed in `2026.1`.

## Limits of the table

- **Rates are not dated.** The table holds today's rates only; a calculation
  for a date before a change (for example a Romanian invoice from July 2025)
  still gets today's rate. The change dates above are recorded here for that
  reason.
- **No product categories.** Which goods fall under which rate changes more
  often than the rates themselves (for example, Slovakia moved several foods
  from 19 % to 23 % on 2026-01-01). Callers choose the rate key.
- Romania's transitional 9 % rate for housing ended on 2026-07-31 and is not
  in the table.

## Notes on the sources

- **Greece:** TEDB's latest Greek data is dated 2025-01-01. AADE's guidance
  and the EPRS overview (TEDB at 2025-07-01) give 24 / 13 / 6 % on the
  mainland, unchanged. TEDB also tags a 4 % "super-reduced" rate (one
  category: works removing barriers for disabled people) and a 13 % "parking"
  rate (agricultural equipment) for Greece. Neither appears in AADE's rate
  list, so they are not in the table; this is the least certain entry.
- **Austria parking 13 %** and **Ireland parking 13.5 %** are unchanged from
  1.0.x. TEDB lists these values as reduced rates, not as parking rates; they
  are kept so that `parking` lookups do not change.
- **Luxembourg 14 %** is kept as the parking rate; TEDB lists it both as a
  parking and a reduced rate.
- **Ireland 4.8 %** (livestock) is not in TEDB; Revenue lists it as current.
- **Malta 12 %** is tagged as a parking rate in TEDB; the Malta Tax and
  Customs Administration lists it among its reduced rates.
- The AADE, MTCA and Lithuanian VMI pages refuse automated requests (HTTP
  403), and the Cyprus Tax Department page now redirects to a landing page.
  Their content was read through search-engine extracts of those official
  pages; it agrees with TEDB in every case.

## Sources (all read on 2026-10-05)

- European Commission, TEDB VAT retrieval service, queried for all 27 member
  states at the dates listed above:
  https://ec.europa.eu/taxation_customs/tedb/ws/VatRetrievalService.wsdl
- European Commission, VAT rates page (points to TEDB):
  https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/vat-rates_en
- European Parliamentary Research Service, "Highs and lows: VAT rate-setting
  in the European Union", January 2026, Table 3 (TEDB rates at 2025-07-01):
  https://www.europarl.europa.eu/RegData/etudes/BRIE/2026/782613/EPRS_BRI(2026)782613_EN.pdf
- Austria, Unternehmensserviceportal, "Neuer Umsatzsteuersatz von 4,9 Prozent":
  https://www.usp.gv.at/aktuelles/newsliste/neuer_ustsatz.html
- Austria, Parliament, Bundesrat approves the cut (PK0513, 2026-06-03):
  https://www.parlament.gv.at/aktuelles/pk/jahr_2026/pk0513
- Estonia, Tax and Customs Board, VAT rates:
  https://www.emta.ee/en/business-client/taxes-and-payment/value-added-tax/vat-rates-and-supply-exempt-tax
- Finland, Tax Administration, "What will change in taxation in 2026":
  https://www.vero.fi/en/About-us/newsroom/news/uutiset/2025/what-will-change-in-taxation-in-2026/
- Lithuania, State Tax Inspectorate, reduced 12 % rate and 2026 VAT changes:
  https://www.vmi.lt/evmi/lengvatinis-12-proc.-pvm-tarifas-19-str.-
  and https://www.vmi.lt/evmi/pridetines-vertes-mokesciu-pakeitimai-nuo-2026-m.
- Romania, ANAF, "Principalele modificări ale cotelor de TVA, reglementate prin
  Legea nr. 141/2025":
  https://static.anaf.ro/static/10/Anaf/AsistentaContribuabili_r/Cotele_de_TVA_09.2025.pdf
- Slovakia, Financial Administration, "Sadzby dane":
  https://www.financnasprava.sk/sk/podnikatelia/dane/dan-z-pridanej-hodnoty/sadzby-dane
- Ireland, Revenue, current VAT rates:
  https://www.revenue.ie/en/vat/vat-rates/search-vat-rates/current-vat-rates.aspx
- Malta, Tax and Customs Administration, VAT rates:
  https://mtca.gov.mt/business-tax/vat1/vat-compliance/vat-rates/vat-rates
- Cyprus, Tax Department, VAT rates (now at https://www.gov.cy/mof-tax):
  https://www.mof.gov.cy/mof/tax/taxdep.nsf/All/6F2D9F654287FF02C2258251002C8130
- EY Global Tax News, "Cyprus introduces 3% VAT rate" (Law of 2023, in force
  2023-07-21; TEDB carries the same date):
  https://globaltaxnews.ey.com/news/2023-1270-cyprus-introduces-3-percent-vat-rate-and-adds-goods-to-0-percent-vat-list
- Greece, AADE, basic VAT rates:
  https://www.aade.gr/en/services-information/useful-guides/commencement-business-activity/basic-vat-rates
