import random

top_of_range = input("enter the range of number you would like: ")

if int(top_of_range) > 10:
    print("enter a number between 0 and 10")

if top_of_range.isdigit():
    top_of_range = int(top_of_range)
    if top_of_range <= 0:
        print("please enter a number larger than zero next time. ")
        quit()
else:
    print("please enter a number next time")
    quit()

r = random.randint(1, top_of_range)


while True:
    guess = input("Make a guess: ")
    if guess.isdigit():
        guess = int(guess)
    else:
        print("please enter a number next time")
        continue

    if guess == r:
        print("You got it!")
        quit()
    elif guess > r:
        print("you were above the number!")
    else:
        print("you were below the number!")