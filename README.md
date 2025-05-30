# Weather Forecast App
School project, that was forced to be open-sourced.  
A simple Weather app.

# Context
So... I have this one teacher where we do documentation and saw that one of the points was "Installation and operating instructions".   
So I decided to open-source it, as I don't have any public projects on Github.

If the teacher that is grading is reading this:  
**GIVE ME MY C5 CHARGING WIRE BACK!!! PLEASEE!!!! ITS BEEN A MONTH AND A HALF!!!**
# How to run
## Dependancies
```bash
sudo apt-get install docker git docker-compose-plugin
# if you dont have docker enabled
sudo systemctl enable docker --now
```
## Starting
```bash
# In the folder where the docker-compose.yml is located at
sudo docker compose up -d
```
## Viewing
Going to `http://localhost`, you should be able to see what I was forced to make
# License
MIT [License](LICENSE)
