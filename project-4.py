name = input("Please enter your name: ")
print(f"welcome to {name}'s adventure")

answer = input("Your at a dead end, it has to paths right and left. where would you like to go? ")
if answer == "right":
    answer = input("U have come to a wobly bridge, would you like to go on it or go back (bridge/back)? ")
    if answer == "bridge":
        answer = input("You see a Dr.strange do you wan tto talk to him (yes/no)? ")
        if answer == "yes":
            print("You got 100 gold and WON!")
        elif answer == "no":
            print("He killed you because u left him read.")
        else:
            print("Invalid option You lose!")
            quit()
    elif answer == back:
        print("You lose!")
    else:
        print("Invalid option You lose!")
        quit()
elif answer == left:
    answer = input("You have reached a lake you can cross it by swimming or walking on the bridge (walk/swim). ")
    if answer == "walk":
        print("you fell down the bridge and died ")
    elif answer == back:
        print("you got eaten by aligators")
    else:
        print("Invalid option You lose!")
        quit()
else:
    print("Invalid option You lose!")
    quit()
