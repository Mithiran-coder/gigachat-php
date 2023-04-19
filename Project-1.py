# this code is written by mithiran with help of https://www.youtube.com/@TechWithTim
# code changes in remote repo by balu
print("Welcome to Mithiran's quiz")

player = input("Do you want to play? y/n ")

if player.lower() != "y":
    print("thanks for visiting us")
    quit()
else:
    print("Lets get started with he quiz")

score = 0

print("only type the ansers in small letters")

answer = input("what is the full form of HAL? ")
if answer.lower() == "hindustan aeronautics limited":
    print("Correct!!!")
    score += 1
else:
    print("Incorect!")

answer = input("who is the PM of india? ")
if answer.lower() == "narendra modi":
    print("Correct!!!")
    score += 1
else:
    print("Incorect!")


answer = input("Who discovered gravity? ")
if answer.lower() == "isaac newton":
    print("Correct!!!")
    score += 1
else:
    print("Incorect!")

print("you got " + str(score) + " question correct")
print("you got " + str(round(((score/3)*100),1)) + "%")

print("thanks for spending your precious time with us")





        
        
