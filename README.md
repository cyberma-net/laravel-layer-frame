# Layer Frame for Laravel
### Layerd structure for your Laravel projects

## Motivation
Laravel's native Active Record approach to models and database provides an easy to use environment
for handling data and database. However, the models are big, they do many different tasks
and therefore don't follow Single Responsibility Principle (SRP). Models tend to get huge over time.

On top of that, active record creates a very tight coupling between the code and the DB structure.

Layer Frame brings a better code structure despite the need of more objects and classes.
Especially bigger projects can benefit from better testability and separation of different tasks.

### Main principles

* Composition over inheritance
* Single responsibility principle
* Layered data flow
* Types wherever possible

## Layers

### Controllers
Controllers validate input data and maps them into the internal represenation, i.e. models.
Then, controllers call a service. After service returns data, controllers
form required data format for the api and ships the data to the API.

### Input Mappers
Contain validation rules for incoming data

### Input Parsers
InputParser is a general class that takes the InputModel and validates the incoming data using the validation rules inside the InputModel. It uses laravel Validator to handle the validation.
If there are special cases for data validation, you can create your own inputParser and inherit the base class. It is not usually needed.


### Services
Services handle business logic

### Repositories

Connects Model Map, Data Mapper and Model Factory, maps model to a raw array
that is sent to the SQL queries.

### Data Mappers
DataMapper is a layer that knows how to communicate with SQL. It contains a few standard queries
and provides interface for any custom queries.

### SQL Database

## Objects

### Input Maps

### Models

Models are basic data handlers. Each model is inherited from Model - beware, this is not an Eloquent model!
The base model defines __set and __get magic methods for attributes and other useful functions.

Be careful about function fill and setAttributes. They differ in handling original data. Check the comments.


### Model Maps

ModelMap is a class that knows how to map the model attributes to the mySQL columns. This is a layer that separates SQL from our code. No other code knows anything about the DB and its structure. This is the main difference to ActiveRecord which uses the column names all over the code, and creates very tight relationship between the DB and the code. That is a very bad approach that only works for smaller projects.

Another advantage of this approach is, that if you create anything new, you mostly only setup the parameters and you don't have to write much new code.

The main parameter of the class is const ATTRIBUTES_MAP. The following parameters are

protected $table = 'users';
protected $primaryKey = 'id';

public $timestamps = true;

These parameters set the table name, primary key name, which in majority cases should be id, we can turn off or on softdeletes and timestamps. In most cases we use both.
The const JSON_COLUMNS contains those columns that are supposed to be transfered from the array type to json.

All classes in this folder inherit ModelMap containing all the important functions.


### Exceptions


Last update: 27th Nov 2023.
