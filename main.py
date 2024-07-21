import requests
from bs4 import BeautifulSoup
import time
import re

def get_amazon_price(url):

    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept-Language': 'en-US,en;q=0.9',
    }

    start_time = time.time()

    response = requests.get(url, headers=headers)

    end_time = time.time()

    elapsed_time = end_time - start_time

    print(f"Elapsed time: {elapsed_time} seconds")

    soup = BeautifulSoup(response.content, 'html.parser')
    
    price = None
    
    # Method 1: Look for the 'priceblock_ourprice' id
    price_element = soup.find(id='priceblock_ourprice')
    if price_element:
        print(1)
        price = price_element.get_text().strip()
    
    
    # Method 2: Look for the 'a-price-whole' and 'a-price-fraction' classes
    if not price:
        print(2)
        price_whole = soup.find('span', class_='a-price-whole')
        price_fraction = soup.find('span', class_='a-price-fraction')
        if price_whole and price_fraction:
            price = f"{price_whole.get_text().strip()}{price_fraction.get_text().strip()}"
    
    # Method 3: Use a more general approach with regex
    if not price:
        print(3)
        price_pattern = re.compile(r'\$\d+(?:\.\d{2})?')
        price_matches = soup.find_all(string=lambda text: isinstance(text, str) and price_pattern.search(text))
        if price_matches:
            price = price_pattern.search(price_matches[0]).group()
    
    # Method 4: Look for 'data-a-price-amount' attribute
    if not price:
        print(4)
        price_element = soup.find(attrs={"data-a-price-amount": True})
        if price_element:
            price = f"${price_element['data-a-price-amount']}"
    
    # Method 5: Look for 'a-offscreen' class (often used for prices)
    if not price:
        print(5)
        price_element = soup.find('span', class_='a-offscreen')
        if price_element:
            price = price_element.get_text().strip()

    return price

url = input("Enter the Amazon product URL: ")
price = get_amazon_price(url)
print(f"The price is: {price}")
print()