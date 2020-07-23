import configparser
import pymysql

host = 'localhost'
user = 'admdb'
password = 'cast1301'
database = 'wsdl_tablas'
port = 3306

def connect():
    result = False
    try:
        config = configparser.ConfigParser()
        config.read('config.ini')
        db = pymysql.connect(host, user, password, database, port)
        result = db 
    except pymysql.Error as identifier:
        print('[ERROR] ocurrio un error conectando la base de datos: {}'.format(identifier))
    return result

def disconnect(db):
    result = False
    try:
        if db is not False:
            db.close()                    
        result = True
    except pymysql.Error as identifier:
        print('[ERROR] ocurrio un error desconectando la base de datos: {}'.format(identifier))
    return result

def executeQuery(db, query):
    result = False
    try:
        if db is False:
            return result
        cursor = db.cursor()
        cursor.execute(query)
        result = cursor
        cursor.close()
    except pymysql.Error as error:
        print('[ERROR] ocurrio un error ejecutando la query: {}'.format(query))
        print('[ERROR] Error: {}'.format(error))
    return result

def executeUpdate(db, query):
    result = False
    try:
        if not db:
            return result
        cursor = db.cursor()
        cursor.execute(query)
        db.commit()
        cursor.close()
        result = True
    except pymysql.Error as error:
        print('[ERROR] ocurrio un error ejecutando la query: {}'.format(query))
        print('[ERROR] Error: {}'.format(error))
    return result