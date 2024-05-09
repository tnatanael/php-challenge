<div align="center">
    <img src="https://media.licdn.com/dms/image/D4E0BAQETyObSEmZH-A/company-logo_200_200/0/1693956448491/jobsity_llc_logo?e=1723075200&v=beta&t=rGq4fY1cprFyIaSabim0_bgb-QLCbJUk6Es9dXuua1w"/>
</div>

## Description
This Challenge is designed to test your abilities building backend web projects in PHP. Show us your best skills and knowledge in good practices and standards.

## Assignment
The objective of this challenge is to create a REST API application that the user can use to track the value of stocks in the stock market. The base for your application as presented here uses a simple implementation of [Slim Framework](https://www.slimframework.com/docs/v3/)

You may use the [Stooq](https://stooq.com/q) API service to get latest stock market values. Check the format used below. You can replace the `{stock_code}` placeholder with the corresponding stock code:

`https://stooq.com/q/l/?s={stock_code}&f=sd2t2ohlcvn&h&e=csv`

## Mandatory Features
 - You will need to **record a video explaining the code** you created, the decisions you made, its functionality, and demonstrating the complete operation of the challenge. _Remember to show the execution from scratch, it should not be running beforehand._

- The application must use a SQL database to store users and record logs of past requests. Check out the Slim documentation if you would like to use [Eloquent](https://www.slimframework.com/docs/v3/cookbook/database-eloquent.html), [Doctrine](https://www.slimframework.com/docs/v3/cookbook/database-doctrine.html) or [Atlas](https://www.slimframework.com/docs/v3/cookbook/database-atlas.html).
- The application must be able to authenticate registered users. There's a basic example of authentication used for 1 endpoint, you may need to modify it accordingly based on the requirements. (See: [routes.php](https://git.jobsity.com/jobsity/php-challenge/-/blob/master/app/routes.php))

The application must have these three endpoints:

 - An endpoint to create a new User, storing the email and information to log in later.
 - An endpoint to request a stock quote, like this:

`GET /stock?q=aapl.us`

```
  {
  "name": "APPLE",
  "symbol": "AAPL.US",
  "open": 123.66,
  "high": 123.66,
  "low": 122.49,
  "close": 123
  }
```

The same endpoint must additionally send an email with the same information to the user who requested the quote. To send the email, you may use [SwiftMailer](https://swiftmailer.symfony.com/docs/introduction.html) with [the wrapper included](https://git.jobsity.com/jobsity/php-challenge/-/blob/master/app/services.php), as we detail in [this example](https://git.jobsity.com/jobsity/php-challenge/-/wikis/Usage-of-the-Mailer-service-(SwiftMailer)), or use your own implementation.
 - An endpoint to retrieve the history of queries made to the API service by that user. The endpoint should return the list of entries saved in the database, showing the latest entries first:

`GET /history`

```
[
    {"date": "2021-04-01T19:20:30Z", "name": "APPLE", "symbol": "AAPL.US", "open": "123.66", "high": 123.66, "low": 122.49, "close": "123"},
    {"date": "2021-03-25T11:10:55Z", "name": "APPLE", "symbol": "AAPL.US", "open": "121.10", "high": 123.66, "low": 122, "close": "122"},
    ...
]
```

## Bonus features
The following features are optional to implement, but if you do, you'll be ranked higher in our evaluation process.

 - Add unit tests for the endpoints.
 - Use RabbitMQ to send the email asynchronously.
 - Use JWT instead of basic authentication for endpoints.
 - Containerize the app.

## Considerations
 - Focus only on the backend. We will not review any frontend functionality for this project.
 - Provide any and all information our evaluators may need. Use seeders instead of SQL dumps if we need any predetermined database information.
 - Provide a detailed Readme with instructions on how to install, use, etc.

## Base project
You can find some bootstrap for the challenge with some endpoints, basic setup, and tests. This project uses composer as a package manager, so in order to run the project for the first time you need to follow these next steps:

- Run `composer install`, this will install dependencies
- Copy the `.env.sample` file into `.env` and modify its contents to match your current settings.
- Run `composer start` and you should be able to check the project running on `http://localhost:8080`

Optional:
- Run `composer test` to see the test suite result
