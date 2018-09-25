# Configuration

To access to Paga+Tarde admin panel, we need to open the Magento admin panel and follow the next steps:

1 – STORES => Configuration
![Step 1](./magento21_step1.png?raw=true "Step 1")

2 – SALES => Payment Methods
![Step 2](./magento21_step2.png?raw=true "Step 2")

3 – Paga+Tarde
![Step 3](./magento21_step3.png?raw=true "Step 3")

In Paga+tarde panel we can set the fields below:

| Field &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;       | Description           | 
| :------------- |:-------------| 
| Enabled      | Yes => Module enabled
|              | No => Módule disabled (Por defecto)
| Public API Key(*) |  String you can get from your [Paga+Tarde profile](https://bo.pagamastarde.com/shop).
| Secret API Key(*) |  String you can get from your [Paga+Tarde profile](https://bo.pagamastarde.com/shop). 
| Title      |  Payment title to show in checkout page. By default:"Financiación instantanea" 
| Checkout description | Description to show in checkout page after payment title. By default:"Paga hasta en 12 cómodas cuotas con Paga+Tarde. Solicitud totalmente online y sinpapeleos, ¡y la respuesta es inmediata!".    
| Minimum cart amount | Minimum amount to use the module and show the payment method to checkout       
| Maximum cart amount | Maximum amount to use the module and show the payment method to checkout       
| Number of installments by default | Number of installments by default to use in simulator
| Maximum number of installments   | Maximum number of installments by default to use in simulator   
| How to open payment  |  Redirect => After checkout, the user will be redirected to Paga+Tarde side to fill the form. Recommended option
|                      |  Iframe => After checkout, the user will watch a pop-up with Paga+Tarde side to fill the form without leave the current page
| Product Simulator    |  Choose if we want to use installments simulator inside product page, in positive case, you can chose the simulator type. Recommended option: MINI
| Checkout Simulator  |   Choose if we want to use installments simulator inside checkout page, in positive case, you can chose the simulator type. Recommended option: MINI
| Price selector   |  Html selector to get the product price inside product page. It will be the amount to use in product simulator (if enabled)
|                  |                                              By default: "div.price-final_price span.price-wrapper span.price"
| Quantity selector  | Html selector to get the number of products to buy inside product page. This amount will be multiplied by product price,
|                    | the resultant amount will be used in checkout simulator (if enabled). Leave blank to disabled.
|                    | By default: "div.fieldset div.qty div.control input.qty"   
| Ok url | Location where user will be redirected after a succesful payment. This string will be concatenated to the base url to build the full url
| Ko url | Location where user will be redirected after a wrong payment. This string will be concatenated to the base url to build the full url 
