import requests
from bs4 import BeautifulSoup
import sys

def get_price():
    url = "https://www.tgju.org/profile/price_aed"
    tag = None
    attempts = 0
    max_attempts = 10

    while not tag and attempts < max_attempts:
        response = requests.get(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        tag = soup.find('span', {'data-col': 'info.last_trade.PDrCotVal'})
        attempts += 1

    if tag:
        price_text = tag.text.strip()
        price = int(price_text.replace(',', ''))
        return price
    else:
        return False

if __name__ == "__main__":
    print(get_price())
