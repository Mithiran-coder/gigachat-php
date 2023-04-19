while True:
    perimeter = input("Please enter the perimeter(q to quit): ")
    if perimeter == "q":
        quit()
    else:
        perimeter = float(perimeter)
        length = perimeter/4
        print(length)
