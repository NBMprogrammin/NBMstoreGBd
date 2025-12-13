<?php
use App\Http\Controllers\FrountEndController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/cod-email-register', [FrountEndController::class, 'sendcodtocreatenewaccounte']);

Route::post('/cod-shange-email', [FrountEndController::class, 'starttoshangeemailprofile'])->middleware('auth:sanctum');
Route::post('/cod-confirmed-shange-email', [FrountEndController::class, 'startconfirmedcodtoshangeemailprofile'])->middleware('auth:sanctum');
Route::post('/register', [FrountEndController::class, 'registerUser']);
Route::post('/login', [FrountEndController::class, 'loginUser']);

Route::post('/password/code', [FrountEndController::class, 'sendResetCode']);
Route::post('/password/verify', [FrountEndController::class, 'verifyResetCode']);

// routes for SMS password reset
Route::post('/sms-password/code', [FrountEndController::class, 'requestCode']);
Route::post('/sms-password/verify', [FrountEndController::class, 'verifyCode']);

// Start show For All Date Profile User //
Route::post('bss/profile-Bss', [FrountEndController::class, 'ProfileUserBess'])->middleware('auth:sanctum');
Route::post('bss/profile-SocailMd', [FrountEndController::class, 'ProfileUserBessSocailMd'])->middleware('auth:sanctum');
Route::post('user/date', [FrountEndController::class, 'ShowDateUserAndProfile'])->middleware('auth:sanctum');
Route::post('My/Profile/update', [FrountEndController::class, 'UpdateOrCreateMyProfileNow'])->middleware('auth:sanctum');
Route::post('My/profile', [FrountEndController::class, 'ShowMyProfileLoginNow'])->middleware('auth:sanctum');
Route::post('My/profile/avatar', [FrountEndController::class, 'ImdateMyAvatarProfiole'])->middleware('auth:sanctum');
Route::post('user/switech-profile', [FrountEndController::class, 'switchProfile'])->middleware('auth:sanctum');
Route::post('bss/Date-Dasboard', [FrountEndController::class, 'MyTotalDatShow'])->middleware('auth:sanctum');
Route::post('User/Update-Phone', [FrountEndController::class, 'ShangeMyNumberPhoneForMyProfile'])->middleware('auth:sanctum');

// Start Alls Action For Profile User
Route::post('user/Update-profile', [FrountEndController::class, 'StartUpdateProfileUser'])->middleware('auth:sanctum');
Route::post('user/Update-password', [FrountEndController::class, 'StartUpdatePasswordUserProf'])->middleware('auth:sanctum');

// End Alls Action For Profile User

Route::middleware('auth:sanctum')->post('/current-profile', function (Request $request) {
    $user = $request->user()->load('curretprofile');
    return response()->json($user->curretprofile);
});


//=== Start show For All Date Profile User ===//


// Start Action All Setting For User Bss 
Route::post('bss/setting-passoword', [FrountEndController::class, 'PsswordSettingAction'])->middleware('auth:sanctum');
Route::post('bss/setting-payment', [FrountEndController::class, 'AddSettingPaymentForBss'])->middleware('auth:sanctum');
Route::post('bss/setting-payment/show', [FrountEndController::class, 'ShowMyPaymentsMethodsBss'])->middleware('auth:sanctum');
Route::post('bss/setting-payment/current-cantry', [FrountEndController::class, 'AddCurrentCantryForBss'])->middleware('auth:sanctum');
Route::post('bss/setting-payment/all', [FrountEndController::class, 'AllDatePaymentMethodBssToShow'])->middleware('auth:sanctum');
Route::post('bss/setting-payment-Active/{PaymentID}', [FrountEndController::class, 'ActiveMyPaymentSettings'])->middleware('auth:sanctum');
Route::post('bss/setting-payment-DscActive/{PaymentID}', [FrountEndController::class, 'DscActiveMyPaymentSettings'])->middleware('auth:sanctum');
Route::post('bss/setting-payment-Update/{PaymentID}', [FrountEndController::class, 'UpdateMyPaymentSettings'])->middleware('auth:sanctum');

//=== End Action All Setting For User Bss  ===//

// Start All Action For Category User //
Route::post('bss/category-all', [FrountEndController::class, 'ShowAllCategoryUser'])->middleware('auth:sanctum');
Route::post('bss/category', [FrountEndController::class, 'storeCategoryUser'])->middleware('auth:sanctum');
Route::post('bss/category-sereach/{datsereach}', [FrountEndController::class, 'sereachCategoryUser'])->middleware('auth:sanctum');
Route::post('bss/category-update/{categoryID}', [FrountEndController::class, 'updateCategoryUser'])->middleware('auth:sanctum');
// === Start All Action For Category User == //

// Start Api Prodect User
Route::post('user/prodect', [FrountEndController::class, 'CreateProdectForUser'])->middleware('auth:sanctum');
Route::post('sereach-user/{nameUser}', [FrountEndController::class, 'StartSereachForUserData'])->middleware('auth:sanctum');
Route::post('Zeboun/sereach-user/{nameUser}', [FrountEndController::class, 'StartSereachForUserToAddZ'])->middleware('auth:sanctum');

//=== Start Api Prodect User ===//

//=== Start All Action For Prodect User ===//
Route::post('bss/Prodect-Create', [FrountEndController::class, 'StartstoreProdectForBss'])->middleware('auth:sanctum');
Route::post('bss/Prodect-all', [FrountEndController::class, 'ShowAllProdectProfileBss'])->middleware('auth:sanctum');
Route::post('bss/Prodect-show/{prodectID}', [FrountEndController::class, 'ShowAllsProdectDataBss'])->middleware('auth:sanctum');
Route::post('bss/Prodect-update/{prodectID}', [FrountEndController::class, 'UpdateProdectBssForId'])->middleware('auth:sanctum');
Route::post('bss/Prodect-sereach-category/{categoryId}', [FrountEndController::class, 'SereachProdectForCategoryNameToGetAll'])->middleware('auth:sanctum');
Route::post('bss/Prodect-sereach/{idProd}', [FrountEndController::class, 'sereachProdectForName'])->middleware('auth:sanctum');
Route::post('Bss/Prodect-Active-Pay/{prodectID}', [FrountEndController::class, 'ActivePayProdectForId'])->middleware('auth:sanctum');
Route::post('Bss/Prodect-DscActive-Pay/{prodectID}', [FrountEndController::class, 'DscActivePayProdectForId'])->middleware('auth:sanctum');
Route::post('Bss/Prodect-Update-Storage/{prodectID}', [FrountEndController::class, 'UpdateStorageProdectForId'])->middleware('auth:sanctum');

// Start Api For Zeboune User Bss
Route::post('bss/Add-Zeboune', [FrountEndController::class, 'AddZebouneForUseBss'])->middleware('auth:sanctum');
Route::post('My-Zebouneys', [FrountEndController::class, 'GetAllMyZebounes'])->middleware('auth:sanctum');
Route::post('My-Zebouneys/Show', [FrountEndController::class, 'GetAllMyZebounesToSheckClick'])->middleware('auth:sanctum');
Route::post('Zebouneys/data/{ZebouneID}', [FrountEndController::class, 'ShowMyZebouneDataForID'])->middleware('auth:sanctum');
Route::post('Bss/Zebouneys-Sereach/{ZebouneID}', [FrountEndController::class, 'ShowMyZebouneForSereachNumberFphone'])->middleware('auth:sanctum');
Route::post('bss/Zebouneys-Deyn/{ZebouneID}', [FrountEndController::class, 'StartActiveDeunFoeMyZeboune'])->middleware('auth:sanctum');
Route::post('bss/Zebouneys-StopDeyn/{ZebouneID}', [FrountEndController::class, 'StopDeynForMyZeboune'])->middleware('auth:sanctum');
Route::post('bss/Zebouneys-UpdateDeyn/{ZebouneID}', [FrountEndController::class, 'StartUpdateDeynForMyZeboune'])->middleware('auth:sanctum');
// End Api For Zeboune User Bss
 
// Start To Api Message User
Route::post('user/Message', [FrountEndController::class, 'ShowMyAllMessage'])->middleware('auth:sanctum');
Route::post('user/Message-send', [FrountEndController::class, 'SendMyMessageToEnthUser'])->middleware('auth:sanctum');
Route::post('user/Message-confirmed-zeboune/{MessageID}', [FrountEndController::class, 'StoreConfirmedMyMessageForaddZeboune'])->middleware('auth:sanctum');
Route::post('user/Message-Close-zeboune/{MessageID}', [FrountEndController::class, 'StoreColseMyMessageForaddZeboune'])->middleware('auth:sanctum');


Route::post('Bss/Message-send-mewve/{UserId}', [FrountEndController::class, 'SendMyMessageToAddUserMewve'])->middleware('auth:sanctum');
Route::post('user/Message-confirmed-Tewve/{MessageID}', [FrountEndController::class, 'StoreConfirmedMyMessageForaddTewve'])->middleware('auth:sanctum');
Route::post('user/Message-Close-Tewve/{MessageID}', [FrountEndController::class, 'StoreColseMyMessageForaddTewve'])->middleware('auth:sanctum');
Route::post('user/Message-ConfRatibe-Tewve/{MessageID}', [FrountEndController::class, 'StoreConfirmedMyRatebeTrave'])->middleware('auth:sanctum');
Route::post('user/Message-DscconfRatibe-Tewve/{MessageID}', [FrountEndController::class, 'StoreDscConfirmedMyRatebeTrave'])->middleware('auth:sanctum');

// End To Api Message User

// Start Api Route Payment Prodect To Do Alss Actions
// Start Router For Show My Alls Payment Prodects
Route::post('bss/Show-All-MyPayment/Prodect', [FrountEndController::class, 'ShowMyAllsPaymentProdects'])->middleware('auth:sanctum');
// === End Router For Show My Alls Payment Prodects ===//
Route::post('bss/payment-prodect', [FrountEndController::class, 'StoragePayMyProdectConfirmed'])->middleware('auth:sanctum');
Route::post('bss/payment-prodect-Confirmed/{PaymentID}', [FrountEndController::class, 'ActiveConfirmendPAaymentProdects'])->middleware('auth:sanctum');
Route::post('bss/payment-prodect-DesConfirmed/{PaymentID}', [FrountEndController::class, 'DescConfirmendPAaymentProdects'])->middleware('auth:sanctum');
Route::post('bss/payment-prodect-Show/{PaymentID}', [FrountEndController::class, 'ShowMyPaymentProdectID'])->middleware('auth:sanctum');
Route::post('bss/payment-prodect-Search/{PaymentID}', [FrountEndController::class, 'SearchForMyZebouneIsDoPaymentProdect'])->middleware('auth:sanctum');
// End Api Route Payment Prodect To Do Alss Actions

// Start Api Route Order Py Order Prodect To Zeboune
Route::post('user/Order-add', [FrountEndController::class, 'CreateOrderForPaymMentProdects'])->middleware('auth:sanctum');
Route::post('user/Order-Show', [FrountEndController::class, 'ShowAllMyOrders'])->middleware('auth:sanctum');
Route::post('user/Order-For-Bss/{BssId}', [FrountEndController::class, 'SearchAllMyOrderForThisBss'])->middleware('auth:sanctum');
Route::post('user/Order-Show/{OrderId}', [FrountEndController::class, 'ShowMyOrdersForId'])->middleware('auth:sanctum');
Route::post('user/Order-stop/{OrderId}', [FrountEndController::class, 'StopSendConfirmedMyOder'])->middleware('auth:sanctum');
Route::post('user/Order-delete/{OrderId}', [FrountEndController::class, 'DeleteSendConfirmedMyOder'])->middleware('auth:sanctum');
Route::post('user/My-Calyane', [FrountEndController::class, 'getAllMyCalyanes'])->middleware('auth:sanctum');
Route::post('user/My-Calyane/Payments', [FrountEndController::class, 'getAllMyCalyanesPayments'])->middleware('auth:sanctum');
Route::post('user/My-Calyane/Prodects', [FrountEndController::class, 'getAllMyCalyanesProdects'])->middleware('auth:sanctum');

// Start Api Route Order Py Order Prodect To Bss
Route::post('bss/Order-For-zeboune/{iDZeboune}', [FrountEndController::class, 'SearchOllOrderForZeboune'])->middleware('auth:sanctum');
Route::post('bss/Order-confirmed-payment/{OrderId}', [FrountEndController::class, 'HandleConfirmedPaymentOrderZ'])->middleware('auth:sanctum');
Route::post('bss/Order-Dscconfirmed-payment/{OrderId}', [FrountEndController::class, 'HandleDscConfirmedPaymentOrderZ'])->middleware('auth:sanctum');
Route::post('bss/Order-confirmed-order/{OrderId}', [FrountEndController::class, 'HandleConfirmedOrderMyZeboune'])->middleware('auth:sanctum');
Route::post('bss/Order-Dscconfirmed-order/{OrderId}', [FrountEndController::class, 'HandleDscConfirmedOrderMyZeboune'])->middleware('auth:sanctum');
// End Api Route Order Py Order Prodect To Bss

// Start Api Route Order Py Order Prodect To Zeboune
Route::post('bss/EdartMeweve', [FrountEndController::class, 'IndexEdarteMyTewiveBss'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-Add/{idOrderMeweve}', [FrountEndController::class, 'AddAygenMeweveToTrave'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-Update-Ratibe/{idOrderMeweve}', [FrountEndController::class, 'StartUpdateRatibeMeweveForBss'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-Get-Ratibe/{idOrderMeweve}', [FrountEndController::class, 'StartGetRatibeToMeweveForBss'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-Show/{idOrderMeweve}', [FrountEndController::class, 'ShowMyDataMeweveTraveForBss'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-Stop/{idOrderMeweve}', [FrountEndController::class, 'StopAddMeweveToTraveForBss'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-EdartMany/{idOrderMeweve}', [FrountEndController::class, 'ActiveEdartManyBssForMeweveToTrave'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-Stop-EdartMany/{idOrderMeweve}', [FrountEndController::class, 'DscActiveEdartManyBssForMeweveToTrave'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-PayProdects/{idOrderMeweve}', [FrountEndController::class, 'ActiveEdartPayProdectsBssForMeweveToTrave'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-Stop-PayProdects/{idOrderMeweve}', [FrountEndController::class, 'DscActiveEdartPayProdectsBssForMeweveToTrave'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-Orders/{idOrderMeweve}', [FrountEndController::class, 'ActiveEdartOrdersBssForMeweveToTrave'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-Stop-Orders/{idOrderMeweve}', [FrountEndController::class, 'DscActiveEdartOrdersBssForMeweveToTrave'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-PaymentEcteronect/{idOrderMeweve}', [FrountEndController::class, 'ActiveEdartPaymentEcteronectForMeweveToTrave'])->middleware('auth:sanctum');
Route::post('bss/EdartMeweve-Stop-PaymentEcteronect/{idOrderMeweve}', [FrountEndController::class, 'DscActiveEdartPaymentEcteronectForMeweveToTrave'])->middleware('auth:sanctum');


// Start Route Api For Edart Maney
Route::post('bss/Edart-Maney/add', [FrountEndController::class, 'CreateOneEdartManeForDay'])->middleware('auth:sanctum');
Route::post('bss/Edart-Maney-Show', [FrountEndController::class, 'ShowAllsDataEdartMane'])->middleware('auth:sanctum');
Route::post('bss/Edart-Maney-update/{id}', [FrountEndController::class, 'UpdateOneEdartMane'])->middleware('auth:sanctum');

// Start Route Api For Edart Maney

// End Api Route Order Py Order Prodect To Zeboune

Route::post('/logout', [FrountEndController::class, 'logoutUser'])->middleware('auth:sanctum');
//=== Start All Action For Prodect User ===//
