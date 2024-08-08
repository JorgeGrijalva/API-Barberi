<?php

use App\Helpers\ResponseError;
use App\Models\Translation;

$e = new ResponseError;
$languages = Translation::where('locale', request('lang', 'lt'))
    ->pluck('value', 'key')
    ->toArray();

return  [
    $e::NO_ERROR  => $languages['NO_ERROR']  ?? "Sėkmingai",
    $e::ERROR_100 => $languages['ERROR_100'] ?? "Vartotojas neprisijungęs.",
    $e::ERROR_101 => $languages['ERROR_101'] ?? "Vartotojas neturi tinkamų vaidmenų.",
    $e::ERROR_102 => $languages['ERROR_102'] ?? "Neteisingas prisijungimo vardas arba slaptažodis.",
    $e::ERROR_103 => $languages['ERROR_103'] ?? "Vartotojo el. pašto adresas nepatvirtintas.",
    $e::ERROR_104 => $languages['ERROR_104'] ?? "Vartotojo telefono numeris nepatvirtintas.",
    $e::ERROR_105 => $languages['ERROR_105'] ?? "Vartotojo paskyra nepatvirtinta.",
    $e::ERROR_106 => $languages['ERROR_106'] ?? "Toks vartotojas jau yra.",
    $e::ERROR_107 => $languages['ERROR_107'] ?? "Prisijunkite naudodami socialinius tinklus.",
    $e::ERROR_108 => $languages['ERROR_108'] ?? "Vartotojas neturi Piniginės.",
    $e::ERROR_109 => $languages['ERROR_109'] ?? "Nepakankamas piniginės likutis.",
    $e::ERROR_110 => $languages['ERROR_110'] ?? "Nepavyko atnaujinti šio vartotojo vaidmens.",
    $e::ERROR_111 => $languages['ERROR_111'] ?? "Pirkti galite tik :quantity prekes.",
    $e::ERROR_112 => $languages['ERROR_112'] ?? 'kai būsena: :verify turėtumėte pridėti tekstą $verify_code ant kūno ir alternatyvaus turinio',
    $e::ERROR_113 => $languages['ERROR_113'] ?? "Siuntėjas neturi piniginės",
    $e::ERROR_114 => $languages['ERROR_114'] ?? "Pardavėjas neturi piniginės",
    $e::ERROR_115 => $languages['ERROR_115'] ?? "Telefonas nerastas",
    $e::ERROR_116 => $languages['ERROR_116'] ?? "Skelbimai jau suaktyvinti",
    $e::ERROR_117 => $languages['ERROR_117'] ?? "Telefonas reikalingas",
    $e::ERROR_118 => $languages['ERROR_118'] ?? "Parduotuvė uždaryta",
    $e::ERROR_119 => $languages['ERROR_119'] ?? "Pasikartojančios atsargos",

    $e::ERROR_201 => $languages['ERROR_201'] ?? "Neteisingas OTP kodas",
    $e::ERROR_202 => $languages['ERROR_202'] ?? "Per daug užklausų, bandykite vėliau",
    $e::ERROR_203 => $languages['ERROR_203'] ?? "OTP kodas nebegalioja",
    $e::ERROR_204 => $languages['ERROR_204'] ?? "Jūs dar nesate pardavėjas arba jūsų parduotuvė nesukurta",
    $e::ERROR_205 => $languages['ERROR_205'] ?? "Parduotuvė jau sukurta",
    $e::ERROR_206 => $languages['ERROR_206'] ?? "Vartotojas jau turi parduotuvę",
    $e::ERROR_207 => $languages['ERROR_207'] ?? "Nepavyko atnaujinti parduotuvės pardavėjo",
    $e::ERROR_208 => $languages['ERROR_208'] ?? "Prenumerata jau aktyvi",
    $e::ERROR_209 => $languages['ERROR_209'] ?? "Parduotuvės pristatymo zona jau sukurta",
    $e::ERROR_210 => $languages['ERROR_210'] ?? "Pristatymas jau pridedamas",
    $e::ERROR_211 => $languages['ERROR_211'] ?? "neteisingas siuntėjas arba prieigos raktas nerastas",
    $e::ERROR_212 => $languages['ERROR_212'] ?? "Ne tavo parduotuvė. ",
    $e::ERROR_213 => $languages['ERROR_213'] ?? "Parduotuvė yra pagrindinė",
    $e::ERROR_214 => $languages['ERROR_214'] ?? "Jūs nesate parduotuvė",
    $e::ERROR_215 => $languages['ERROR_215'] ?? "Neteisingas kodas arba prieigos raktas nebegalioja",
    $e::ERROR_216 => $languages['ERROR_216'] ?? "Patvirtinkite kodo siuntimą",
    $e::ERROR_217 => $languages['ERROR_217'] ?? "Vartotojas siunčia el",
    $e::ERROR_218 => $languages['ERROR_218'] ?? "neįjungtas",
    $e::ERROR_219 => $languages['ERROR_219'] ?? "Jūsų prenumeratos galiojimas baigiasi",
    $e::ERROR_220 => $languages['ERROR_220'] ?? "Baigėsi prenumeratos produktų limitas",
    $e::ERROR_249 => $languages['ERROR_249'] ?? "Netinkamas kuponas",
    $e::ERROR_250 => $languages['ERROR_250'] ?? "Baigėsi kupono galiojimo laikas",
    $e::ERROR_251 => $languages['ERROR_251'] ?? "Kuponas jau panaudotas",
    $e::ERROR_252 => $languages['ERROR_252'] ?? "Būsena jau naudojama",
    $e::ERROR_253 => $languages['ERROR_253'] ?? "Netinkamas būsenos tipas",
    $e::ERROR_254 => $languages['ERROR_254'] ?? "Nepavyko atnaujinti būsenos Atšaukti",
    $e::ERROR_255 => $languages['ERROR_255'] ?? "Negalima atnaujinti užsakymo būsenos, jei užsakymas jau išsiųstas arba pristatytas",

    $e::ERROR_400 => $languages['ERROR_400'] ?? "Bloga užklausa.",
    $e::ERROR_401 => $languages['ERROR_401'] ?? "Neleistina.",
    $e::ERROR_403 => $languages['ERROR_403'] ?? "Jūsų projektas nesuaktyvintas.",
    $e::ERROR_404 => $languages['ERROR_404'] ?? "Prekė nerasta.",
    $e::ERROR_415 => $languages['ERROR_415'] ?? "Nėra ryšio su duomenų baze",
    $e::ERROR_422 => $languages['ERROR_422'] ?? "Patvirtinimo klaida",
    $e::ERROR_429 => $languages['ERROR_429'] ?? "Per daug prašymų",
    $e::ERROR_430 => $languages['ERROR_430'] ?? "Prekės kiekis 0",
    $e::ERROR_431 => $languages['ERROR_431'] ?? "Aktyvi numatytoji valiuta nerasta",
    $e::ERROR_432 => $languages['ERROR_432'] ?? "Neapibrėžtas tipas",
    $e::ERROR_434 => $languages['ERROR_434'] ?? "Mokėjimo būdas turi būti tik piniginė arba grynieji pinigai",
    $e::ERROR_435 => $languages['ERROR_435'] ?? "Parduotuvė uždaryta",
    $e::ERROR_436 => $languages['ERROR_436'] ?? ":shop nepristato jūsų adresu. ",

    $e::ERROR_501 => $languages['ERROR_501'] ?? "Klaida kuriant",
    $e::ERROR_502 => $languages['ERROR_502'] ?? "Klaida atnaujinant",
    $e::ERROR_503 => $languages['ERROR_503'] ?? "Trinimo metu įvyko klaida.",
    $e::ERROR_504 => $languages['ERROR_504'] ?? "Negalima ištrinti įrašo, kuriame yra verčių.",
    $e::ERROR_505 => $languages['ERROR_505'] ?? "Negalima ištrinti numatytojo įrašo. # :ids",
    $e::ERROR_506 => $languages['ERROR_506'] ?? "Jau egzistuoja.",
    $e::ERROR_507 => $languages['ERROR_507'] ?? "Nepavyko ištrinti įrašo, kuriame yra produktų.",
    $e::ERROR_508 => $languages['ERROR_508'] ?? "„Excel“ formatas neteisingas arba duomenys neteisingi.",
    $e::ERROR_509 => $languages['ERROR_509'] ?? "Netinkamas datos formatas.",
    $e::ERROR_510 => $languages['ERROR_510'] ?? "Adresas teisingas.",

    $e::EMPTY   => $languages['EMPTY']   ?? "Tuščia",
    $e::FIN_FO  => $languages['FIN_FO']  ?? "Jums reikia php failo informacijos plėtinio",
    $e::SUCCESS => $languages['SUCCESS'] ?? "Sėkmė",

    $e::NEW_ORDER  => $languages['NEW_ORDER']  ?? "Naujas užsakymas tau # :id",
    $e::OTHER_SHOP => $languages['OTHER_SHOP'] ?? "Kita parduotuvė",

    $e::NEW_MESSAGE   => $languages['NEW_MESSAGE']   ?? 'Tvarka yra message from :from',
    $e::ORDER_POINT   => $languages['ORDER_POINT']   ?? "Tvarka yra taškas",
    $e::ADD_CASHBACK  => $languages['ADD_CASHBACK']  ?? "Pridėtas pinigų grąžinimas",
    $e::EMPTY_STATUS  => $languages['EMPTY_STATUS']  ?? "Būsena tuščia",
    $e::WALLET_TOP_UP => $languages['WALLET_TOP_UP'] ?? ":sender papildykite savo piniginę",

    $e::ORDER_REFUNDED => $languages['ORDER_REFUNDED'] ?? "Užsakymas grąžintas",
    $e::SHOP_NOT_FOUND => $languages['SHOP_NOT_FOUND'] ?? "Parduotuvė nerasta",
    $e::USER_NOT_FOUND => $languages['USER_NOT_FOUND'] ?? "Vartotojas nerastas",
    $e::USER_IS_BANNED => $languages['USER_IS_BANNED'] ?? "Vartotojas užblokuotas!",
    $e::NOT_IN_POLYGON => $languages['NOT_IN_POLYGON'] ?? "Ne daugiakampyje",
    $e::STATUS_CHANGED => $languages['STATUS_CHANGED'] ?? "Jūsų užsakymas",

    $e::TYPE_PRICE_USER => $languages['TYPE_PRICE_USER'] ?? "Tipo, kainos arba naudotojo laukas tuščias",
    $e::ORDER_NOT_FOUND => $languages['ORDER_NOT_FOUND'] ?? "Užsakymas nerastas",
    $e::REPLACE_PRODUCT => $languages['REPLACE_PRODUCT'] ?? "Pakeiskite gaminį #:id",
    $e::WALLET_WITHDRAW => $languages['WALLET_WITHDRAW'] ?? ":sender išima savo piniginę",
    $e::PAYOUT_ACCEPTED => $languages['PAYOUT_ACCEPTED'] ?? "Išmokėjimas jau :status",
    $e::CANT_DELETE_IDS => $languages['CANT_DELETE_IDS'] ?? "Negalima ištrinti :ids",

    $e::NEW_PARCEL_ORDER  => $languages['NEW_PARCEL_ORDER'] ?? "Naujas siuntinio užsakymas jums # :id",
    $e::PRODUCTS_IS_EMPTY => $languages['PRODUCTS_IS_EMPTY'] ?? "Prekės tuščios",
    $e::CONFIRMATION_CODE => $languages['CONFIRMATION_CODE'] ?? "Patvirtinimo kodas :code",
    $e::NOTHING_TO_UPDATE => $languages['NOTHING_TO_UPDATE'] ?? "Nėra ką atnaujinti",

    $e::VIA_WALLET         => $languages['VIA_WALLET'] ?? "Mokėjimas :type #:id per Piniginę",
    $e::CURRENCY_NOT_FOUND => $languages['CURRENCY_NOT_FOUND'] ?? "Valiuta nerasta",
    $e::LANGUAGE_NOT_FOUND => $languages['LANGUAGE_NOT_FOUND'] ?? "Kalba nerasta",
    $e::CATEGORY_IS_PARENT => $languages['CATEGORY_IS_PARENT'] ?? "Kategorija yra pagrindinė",
    $e::CANT_DELETE_ORDERS => $languages['CANT_DELETE_ORDERS'] ?? "Negalima ištrinti užsakymų :ids",
    $e::CANT_UPDATE_ORDERS => $languages['CANT_UPDATE_ORDERS'] ?? "Negaliu atnaujinti užsakymų :ids",

    $e::SHOP_STATUS_CHANGED => $languages['SHOP_STATUS_CHANGED'] ?? "Jūsų užsakymo #:id būsena pakeista į :status",
    $e::USER_CARTS_IS_EMPTY => $languages['USER_CARTS_IS_EMPTY'] ?? "Vartotojų krepšeliai tušti",

    $e::NOT_IN_PARCEL_POLYGON   => $languages['NOT_IN_PARCEL_POLYGON'] ?? "Mūsų paslauga šiuo atstumu neveikia, prašome pasirinkti :km kitą tipą arba kitą adresą. Riba :km km",
    $e::CANT_UPDATE_EMPTY_ORDER => $languages['CANT_UPDATE_EMPTY_ORDER'] ?? "Negalima sukurti ar atnaujinti tuščios užsakymo",

    $e::INCORRECT_LOGIN_PROVIDER => $languages['INCORRECT_LOGIN_PROVIDER'] ?? "Prašome prisijungti per facebook arba google.",
    $e::PHONE_OR_EMAIL_NOT_FOUND => $languages['PHONE_OR_EMAIL_NOT_FOUND'] ?? "Telefonas arba el. pašto adresas nerastas",

    $e::DELIVERYMAN_SETTING_EMPTY   => $languages['DELIVERYMAN_SETTING_EMPTY'] ?? "Jūsų nustatymas tuščias",
    $e::DELIVERYMAN_IS_NOT_CHANGED  => $languages['DELIVERYMAN_IS_NOT_CHANGED'] ?? "Jums reikia keitimo siuntėjo",
    $e::IMAGE_SUCCESSFULLY_UPLOADED => $languages['IMAGE_SUCCESSFULLY_UPLOADED'] ?? "Sėkmė :title, :type",

    $e::USER_SUCCESSFULLY_REGISTERED  => $languages['USER_SUCCESSFULLY_REGISTERED'] ?? "Vartotojas sėkmingai užsiregistravo",
    $e::ORDER_OR_DELIVERYMAN_IS_EMPTY => $languages['ORDER_OR_DELIVERYMAN_IS_EMPTY'] ?? "Užsakymas nerastas arba siuntėjas nepridėtas",

    $e::RECORD_WAS_SUCCESSFULLY_CREATED => $languages['RECORD_WAS_SUCCESSFULLY_CREATED'] ?? "Įrašas sėkmingai sukurtas",
    $e::RECORD_WAS_SUCCESSFULLY_UPDATED => $languages['RECORD_WAS_SUCCESSFULLY_UPDATED'] ?? "Įrašas sėkmingai atnaujintas",
    $e::RECORD_WAS_SUCCESSFULLY_DELETED => $languages['RECORD_WAS_SUCCESSFULLY_DELETED'] ?? "Įrašas sėkmingai ištrintas",

    $e::TAX   => $languages['TAX']   ?? "PVM",
    $e::DATE  => $languages['DATE']  ?? "Data",
    $e::FROM  => $languages['FROM']  ?? "Sukūrimo data",
    $e::ORDER => $languages['ORDER'] ?? "Įsakymas",
    $e::PRICE => $languages['PRICE'] ?? "Kaina",

    $e::COUPON  => $languages['COUPON']  ?? "Kuponas",
    $e::NUMBER  => $languages['NUMBER']  ?? "Numeris",
    $e::DETAILS => $languages['DETAILS'] ?? "Detalės",
    $e::PRODUCT => $languages['PRODUCT'] ?? "Produktas",
    $e::INVOICE => $languages['INVOICE'] ?? "Sąskaita",

    $e::DISCOUNT  => $languages['DISCOUNT']  ?? "Nuolaida",
    $e::QUANTITY  => $languages['QUANTITY']  ?? "Kiekis",
    $e::TOTAL_TAX => $languages['TOTAL_TAX'] ?? "PVM",

    $e::SERVICE_FEE   => $languages['SERVICE_FEE']   ?? "Platformos mokestis",
    $e::TOTAL_PRICE   => $languages['TOTAL_PRICE']   ?? "Iš viso",
    $e::DELIVERY_FEE  => $languages['DELIVERY_FEE']  ?? "Pristatymas",
    $e::ADDRESS_PLACE => $languages['ADDRESS_PLACE'] ?? "Pirkėjas",

    $e::SEND_GIFT_CART          => $languages['SEND_GIFT_CART']          ?? ':sender atsiuntė jums dovanų kuponą',
    $e::PRICE_WITHOUT_TAX       => $languages['PRICE_WITHOUT_TAX']       ?? "Kaina Be PVM",
    $e::DELIVERY_DATE_TIME      => $languages['DELIVERY_DATE_TIME']      ?? "Pristatymo data",
    $e::TOTAL_PRICE_WITHOUT_TAX => $languages['TOTAL_PRICE_WITHOUT_TAX'] ?? "Visa Suma",

];
