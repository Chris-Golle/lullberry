#!/usr/local/bin/python3

# importing the requests library
import requests


##############################################################################################################################################################################

To modify accordingly

##############################################################################################################################################################################

#Set total price
price = 18.5

#Authentification with the API key in base64
headers = {'Authorization': 'Basic XXXXXXXX'}

#Uploaded image ids
for file_id in YYY, ZZZ:
    
##############################################################################################################################################################################
    
    
    title = "Tshirt "+str(file_id)
    data_product =     {
        "sync_product": {
            "name": title,
            "external_id": file_id
                },
            "sync_variants": [
                              {
                              "retail_price": price,
                              "variant_id": 8474,
                              "files": [
                                        {
                                        "id": file_id
                                        },
                                        ]
                              }]}
    r = requests.post(url = 'https://api.printful.com/store/products', json = data_product, headers = headers)
    pastebin_url = r.text
    print("The pastebin URL is:%s"%pastebin_url)

        
    #Select t-shirt and colors (add 0, 1, 2, 3 or 4 for S/M/L/XL/2XL)
    for tshirt in 8481, 8495, 8488, 8495, 9381, 9367, 4897, 6309, 4917, 4907, 4942:

            #Set price, and
            data_variant = {
                            "retail_price": price,
                            "variant_id": tshirt,
                            "files": [
                                      {
                                      "id": file_id
                                      },
                                      ]
                }


# sending post request and saving response as response object
            APIENDPOINT = "https://api.printful.com/store/products/@"+str(file_id)+"/variants"
            print(APIENDPOINT)
            r = requests.post(url = APIENDPOINT, json = data_variant, headers = headers)

# extracting response text
            pastebin_url = r.text
            print("The pastebin URL is:%s"%pastebin_url)

