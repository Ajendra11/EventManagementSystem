from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
import time

# Step 1: Initialize WebDriver
driver = webdriver.Chrome()  # Use the appropriate driver (e.g., Chrome, Firefox, Edge)
driver.maximize_window()

# Step 2: Navigate to the Login Page
driver.get("https://cms.periwin.com/")  # Login URL

# Step 3: Locate Username & Password Fields
username_field = driver.find_element(By.NAME, "email")  # Using Name as a Locator
password_field = driver.find_element(By.ID, "password")  # Using ID as a Locator
login_button = driver.find_element(By.XPATH , "//button[contains(text(),'Sign In')]")  # Using XPATH as a Locator

# Step 4: Enter Credentials & Submit
username_field.send_keys("colab@hck.com")
password_field.send_keys("Hck321#@!")
login_button.click()

# Step 5: Verify Login Success or Failure
time.sleep(3)  # Wait for page to load
try:
    dashboard_element = driver.find_element(By.XPATH, "//h2[contains(text(),'Dashboard')]")  # Adjust based on page
    print("Login Successful!")
except:
    error_message = driver.find_element(By.CLASS_NAME, "error-message").text
    print(f"Login Failed: {error_message}")


#Step 6: Click On Profile Icon
    profile_element = driver.find_element(By.ID, "profile")
    profile_element.click()
    time.sleep(10)

#Step 7: Click on Logout
    logout_element = driver.find_element(By.CLASS_NAME, "[class='lni lni-exit']")
    time.sleep(5)
    logout_element.click()

# Step 6: Close Browser
# #driver.quit()
